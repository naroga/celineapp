<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Workspace;
use App\Entity\WorkspaceInvite;
use App\Entity\WorkspaceMembership;
use App\Repository\UserRepository;
use App\Repository\WorkspaceInviteRepository;
use App\Repository\WorkspaceMembershipRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class WorkspaceManager
{
    private const INVITE_EMAIL_SUBJECT = 'You have been invited to a Naroga Assistant workspace';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WorkspaceMembershipRepository $membershipRepository,
        private readonly WorkspaceInviteRepository $inviteRepository,
        private readonly UserRepository $userRepository,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        #[Autowire('%app.workspace_invite_ttl%')]
        private readonly int $inviteTtlInSeconds,
    ) {
    }

    public function createWorkspace(User $owner, string $name): Workspace
    {
        $workspace = new Workspace($owner, $name);
        $membership = new WorkspaceMembership($workspace, $owner, WorkspaceMembership::ROLE_OWNER);

        $this->entityManager->persist($workspace);
        $this->entityManager->persist($membership);
        $this->entityManager->flush();

        return $workspace;
    }

    public function ensureMembership(User $user, Workspace $workspace): ?WorkspaceMembership
    {
        return $this->membershipRepository->findOneByUserAndWorkspace($user, $workspace);
    }

    public function inviteUser(Workspace $workspace, User $inviter, string $email, string $role): WorkspaceInvite
    {
        $membership = $this->ensureMembership($inviter, $workspace);

        if ($membership === null || !$this->canManageInvites($membership)) {
            throw new AccessDeniedException('You do not have permission to invite members to this workspace.');
        }

        $normalizedEmail = mb_strtolower($email);

        $existingUser = $this->userRepository->findOneByEmail($normalizedEmail);

        if ($existingUser !== null) {
            $existingMembership = $this->membershipRepository->findOneByUserAndWorkspace($existingUser, $workspace);

            if ($existingMembership !== null) {
                throw new \DomainException('That user is already a member of this workspace.');
            }
        }

        $pendingInvites = $this->inviteRepository->findPendingForWorkspaceAndEmail($workspace, $normalizedEmail);

        if ($pendingInvites !== []) {
            throw new \DomainException('An invite is already pending for that email address.');
        }

        $invite = new WorkspaceInvite($workspace, $inviter, $normalizedEmail, $role, $this->inviteTtlInSeconds);

        if ($existingUser !== null) {
            $invite->assignInvitedUser($existingUser);
        }

        $this->inviteRepository->save($invite);
        $this->entityManager->flush();

        $this->sendInviteEmail($invite);

        return $invite;
    }

    public function acceptInvite(WorkspaceInvite $invite, User $user): WorkspaceMembership
    {
        $now = new DateTimeImmutable();

        if ($invite->hasExpired($now)) {
            $invite->markExpired($now);
            $this->entityManager->flush();

            throw new \DomainException('That invite has expired.');
        }

        if ($invite->getStatus() !== WorkspaceInvite::STATUS_PENDING) {
            throw new \DomainException('That invite is no longer available.');
        }

        if ($invite->getInvitedUser() !== null && $invite->getInvitedUser()->getId() !== $user->getId()) {
            throw new AccessDeniedException('This invite is linked to another user.');
        }

        if ($invite->getInvitedUser() === null && $invite->getEmail() !== mb_strtolower($user->getEmail())) {
            throw new AccessDeniedException('This invite is tied to another email address.');
        }

        $existingMembership = $this->membershipRepository->findOneByUserAndWorkspace($user, $invite->getWorkspace());

        if ($existingMembership !== null) {
            $invite->assignInvitedUser($user);
            $invite->markAccepted($user, $now);
            $this->entityManager->flush();

            return $existingMembership;
        }

        $membership = new WorkspaceMembership($invite->getWorkspace(), $user, $invite->getRole());

        $this->entityManager->persist($membership);
        $invite->markAccepted($user, $now);
        $this->entityManager->flush();

        return $membership;
    }

    public function declineInvite(WorkspaceInvite $invite, User $user): void
    {
        $now = new DateTimeImmutable();

        if ($invite->getStatus() !== WorkspaceInvite::STATUS_PENDING) {
            throw new \DomainException('That invite is no longer available.');
        }

        if ($invite->hasExpired($now)) {
            $invite->markExpired($now);
            $this->entityManager->flush();

            return;
        }

        if ($invite->getInvitedUser() !== null && $invite->getInvitedUser()->getId() !== $user->getId()) {
            throw new AccessDeniedException('This invite is linked to another user.');
        }

        if ($invite->getInvitedUser() === null && $invite->getEmail() !== mb_strtolower($user->getEmail())) {
            throw new AccessDeniedException('This invite is tied to another email address.');
        }

        $invite->markDeclined($user, $now);
        $this->entityManager->flush();
    }

    /**
     * @return WorkspaceMembership[]
     */
    public function claimInvitesForUser(User $user, ?WorkspaceInvite $tokenInvite = null): array
    {
        $acceptedInvites = [];
        $now = new DateTimeImmutable();

        if ($tokenInvite !== null) {
            $acceptedInvites[] = $this->acceptInvite($tokenInvite, $user);
        }

        foreach ($this->inviteRepository->findClaimableForEmail($user->getEmail(), $now) as $invite) {
            if ($tokenInvite !== null && $invite->getId() === $tokenInvite->getId()) {
                continue;
            }

            $acceptedInvites[] = $this->acceptInvite($invite, $user);
        }

        return $acceptedInvites;
    }

    private function canManageInvites(WorkspaceMembership $membership): bool
    {
        return in_array(
            $membership->getRole(),
            [WorkspaceMembership::ROLE_OWNER, WorkspaceMembership::ROLE_ADMIN],
            true,
        );
    }

    public function userCanManageInvites(WorkspaceMembership $membership): bool
    {
        return $this->canManageInvites($membership);
    }

    private function sendInviteEmail(WorkspaceInvite $invite): void
    {
        $workspace = $invite->getWorkspace();
        $inviter = $invite->getInvitedBy();
        $inviteLink = $this->urlGenerator->generate(
            'spa_workspace_invite',
            [
                'token' => $invite->getToken(),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $email = (new Email())
            ->to(new Address($invite->getEmail()))
            ->subject(self::INVITE_EMAIL_SUBJECT)
            ->html(<<<HTML
<p>Hello,</p>
<p>{$inviter->getFirstName()} {$inviter->getLastName()} invited you to collaborate in the "{$workspace->getName()}" workspace on Naroga Assistant.</p>
<p>Use the link below to accept the invite:</p>
<p><a href="{$inviteLink}">Accept invite</a></p>
<p>The invite will expire on {$invite->getExpiresAt()->format('F j, Y H:i T')}.</p>
HTML);

        $this->mailer->send($email);
    }
}
