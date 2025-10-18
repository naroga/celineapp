<?php

namespace App\Twig;

use App\Service\ViteAssetManifest;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ViteExtension extends AbstractExtension
{
    public function __construct(private readonly ViteAssetManifest $manifest)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('vite_entry_script_tags', [$this, 'renderScriptTags'], ['is_safe' => ['html']]),
            new TwigFunction('vite_entry_link_tags', [$this, 'renderLinkTags'], ['is_safe' => ['html']]),
        ];
    }

    public function renderScriptTags(string $entry): string
    {
        if ($this->manifest->useDevServer()) {
            $url = $this->manifest->getDevServerUrl();
            $entryPath = $this->manifest->resolveDevEntry($entry);
            $scripts = [
                sprintf('<script type="module" src="%s/@vite/client"></script>', $url),
                sprintf('<script type="module" src="%s/%s"></script>', $url, $entryPath),
            ];

            return implode("\n", $scripts);
        }

        $entryData = $this->manifest->getEntry($entry);

        $tags = [sprintf('<script type="module" src="/build/%s"></script>', $entryData['file'])];

        if (!empty($entryData['imports'])) {
            foreach ($entryData['imports'] as $import) {
                $tags[] = sprintf('<link rel="modulepreload" href="/build/%s">', $import);
            }
        }

        return implode("\n", $tags);
    }

    public function renderLinkTags(string $entry): string
    {
        if ($this->manifest->useDevServer()) {
            return '';
        }

        $entryData = $this->manifest->getEntry($entry);

        if (empty($entryData['css'])) {
            return '';
        }

        $links = [];

        foreach ($entryData['css'] as $css) {
            $links[] = sprintf('<link rel="stylesheet" href="/build/%s">', $css);
        }

        return implode("\n", $links);
    }
}
