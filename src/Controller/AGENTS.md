Controllers should be lean. They should handle request validation and response building. All other logic should be deferred to a specific service class. 
- Admin APIs belong under `App\Controller\Admin` and must guard access with `ROLE_ADMIN`, delegating analytics aggregation to dedicated services.
