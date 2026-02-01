# API System

The Freeflow CMS API system is extension-driven. Each extension can register its API routes in `manifest.xml`, and the routes are stored in the `#__api_routes` table at install time.

## Route model

Routes are declared in the manifest using `apiRoutes` nodes. Each route includes:

- `path`: the route path under `/api/ext/{ext_key}/`
- `method`: HTTP method (`GET` or `POST`)
- `controller`: controller class name
- `action`: method name on the controller
- `public`: `1` or `0`
- `permission`: optional permission key

## Example manifest snippet

```
<apiRoutes>
  <route path="status" method="GET" controller="StatusController" action="index" public="1" />
  <route path="items" method="POST" controller="ItemsController" action="create" public="0" permission="manage_items" />
</apiRoutes>
```

## Authentication and permissions

- Public routes: no authentication required.
- Private routes: require a valid session (site or admin).
- If `permission` is set, the user must also pass ACL checks.

## Response format

API responses are XML only. The system provides a helper response format:

```
<response>
  <status>ok</status>
  <message>...</message>
</response>
```

If an error occurs, the response is:

```
<response>
  <status>error</status>
  <message>Forbidden</message>
</response>
```

## Example request

```
GET /api/ext/com_example/status
```

## Example response

```
<response>
  <status>ok</status>
  <message>Service healthy</message>
</response>
```

## ACL expectations

Controllers should not make raw role checks or direct DB connections. Use the core ACL system and the core database connection routines to remain compliant.

## Response helpers

extension should return either:

- A Core Response object (preferred)
- A simple string, which is wrapped into an XML response

Do not output raw JSON or HTML from API actions.

## Public vs private routes

- Public routes are safe for unauthenticated access and should be read-only.
- Private routes require a session and optional permission.

Avoid using private routes for tasks that can be handled through admin pages.
