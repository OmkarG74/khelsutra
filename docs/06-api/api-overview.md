# KhelSutra REST API Overview

## Base URL
All API requests must use the V1 prefix:
```
/api/v1/
```

## Standard Response Structure
All successful API responses return a uniform envelope:
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": {}
}
```

## Standard Error Structure
All error responses adhere to standard HTTP status codes and a uniform JSON structure:
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email field is required."
    ]
  }
}
```

## Supported HTTP Methods
- `GET`: Retrieve resources.
- `POST`: Create a new resource or execute an action.
- `PUT`: Update an existing resource completely.
- `PATCH`: Partially update an existing resource.
- `DELETE`: Remove or soft-delete a resource.
