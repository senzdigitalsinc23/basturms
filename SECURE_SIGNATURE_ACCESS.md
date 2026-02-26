# Secure Signature File Access ✓

## Requirement
Signature files are official staff signatures used to sign documents on the system, so they must be secured and require authentication to access.

## Security Implementation

### 1. Document Type Added
Added `signature` to allowed document types in UploadService:
```php
private const ALLOWED_DOC_TYPES = [
    'profile_picture',
    'signature',           // NEW - for staff signatures
    'staff_signature',
    'student_document',
    'staff_document'
];
```

### 2. Conditional URL Generation
UploadService now returns different URLs based on document type:

**Secure Documents (Authentication Required):**
- `signature`
- `staff_signature`
- `student_document`
- `staff_document`

**Public Documents (No Authentication):**
- `profile_picture`

```php
$secureDocTypes = ['signature', 'staff_signature', 'student_document', 'staff_document'];
if (in_array($docType, $secureDocTypes)) {
    $fullUrl = $appUrl . '/api/v1/uploads/file/' . $uploadId;  // Authenticated
} else {
    $fullUrl = $appUrl . '/api/v1/uploads/public/' . $uploadId;  // Public
}
```

### 3. Public Endpoint Protection
The `/api/v1/uploads/public/{id}` endpoint now blocks access to signatures:

```php
// Block access to signatures and sensitive documents
$blockedDocTypes = ['signature', 'staff_signature', 'student_document', 'staff_document'];
if (in_array($upload['doc_type'], $blockedDocTypes)) {
    return 403 Forbidden;
}
```

## API Behavior

### Upload Signature
```bash
curl -X POST "http://localhost:8000/api/v1/uploads" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@signature.png" \
  -F "doc_type=signature"
```

**Response:**
```json
{
  "success": true,
  "upload_id": 10,
  "url": "http://localhost:8000/api/v1/uploads/file/10",
  "doc_name": "signature.png"
}
```

Note: URL points to `/uploads/file/` (authenticated endpoint)

### Upload Profile Picture
```bash
curl -X POST "http://localhost:8000/api/v1/uploads" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@photo.jpg" \
  -F "doc_type=profile_picture"
```

**Response:**
```json
{
  "success": true,
  "upload_id": 11,
  "url": "http://localhost:8000/api/v1/uploads/public/11",
  "doc_name": "photo.jpg"
}
```

Note: URL points to `/uploads/public/` (public endpoint)

## Access Control

### Authenticated Endpoint: /api/v1/uploads/file/{id}
**Requires:** Authorization header with valid token
**Allows:** All document types
**Middleware:** APIKeyMiddleware, AuthMiddleware

```bash
# Access signature (requires auth)
curl "http://localhost:8000/api/v1/uploads/file/10" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Public Endpoint: /api/v1/uploads/public/{id}
**Requires:** No authentication
**Allows:** Only profile_picture doc_type
**Blocks:** signature, staff_signature, student_document, staff_document
**Middleware:** RateLimiter only

```bash
# Try to access signature via public endpoint - BLOCKED
curl "http://localhost:8000/api/v1/uploads/public/10"

# Response: 403 Forbidden
{
  "success": false,
  "message": "This document type requires authentication. Use /api/v1/uploads/file/{id} instead."
}
```

## Security Layers

### Layer 1: Document Type Check
Blocks signatures by doc_type before checking file type

### Layer 2: File Type Check
Only allows image MIME types (jpeg, png, gif, webp)

### Layer 3: Authentication
Authenticated endpoint requires valid token

## Document Type Security Matrix

| Document Type | Public Access | Auth Required | Use Case |
|--------------|---------------|---------------|----------|
| profile_picture | ✅ Yes | ❌ No | User profile photos |
| signature | ❌ No | ✅ Yes | Staff signatures for documents |
| staff_signature | ❌ No | ✅ Yes | Staff signatures |
| student_document | ❌ No | ✅ Yes | Student documents |
| staff_document | ❌ No | ✅ Yes | Staff documents |

## Frontend Integration

### Display Profile Picture (Public)
```javascript
// No authentication needed
<img src={user.profile_picture.url} alt="Profile" />
```

### Display Signature (Authenticated)
```javascript
// Requires authentication
const fetchSignature = async (signatureUrl) => {
  const response = await fetch(signatureUrl, {
    headers: {
      'Authorization': 'Bearer ' + token
    }
  });
  
  const blob = await response.blob();
  const imageUrl = URL.createObjectURL(blob);
  return imageUrl;
};

// Usage
const signatureUrl = await fetchSignature(staff.signature_url);
<img src={signatureUrl} alt="Signature" />
```

### Alternative: Use Base64 for Signatures
```javascript
const fetchSignatureBase64 = async (signatureUrl) => {
  const response = await fetch(signatureUrl, {
    headers: {
      'Authorization': 'Bearer ' + token
    }
  });
  
  const blob = await response.blob();
  return new Promise((resolve) => {
    const reader = new FileReader();
    reader.onloadend = () => resolve(reader.result);
    reader.readAsDataURL(blob);
  });
};

// Usage
const base64Signature = await fetchSignatureBase64(staff.signature_url);
<img src={base64Signature} alt="Signature" />
```

## Use Cases

### Staff Signature Upload
```bash
# Staff uploads their signature
curl -X POST "http://localhost:8000/api/v1/uploads" \
  -H "Authorization: Bearer STAFF_TOKEN" \
  -F "file=@my_signature.png" \
  -F "doc_type=signature"

# Returns authenticated URL
# Store this URL in staff profile or documents
```

### Document Signing
```bash
# When generating a document, fetch the signature
curl "http://localhost:8000/api/v1/uploads/file/10" \
  -H "Authorization: Bearer STAFF_TOKEN" \
  --output signature.png

# Use signature.png to sign the document
```

## Storage Location
Signatures are stored in:
```
storage/uploads/signatures/
```

Example filename:
```
usr_123456_signature_a3f5b2c8.png
```

## Benefits

1. **Security**: Signatures require authentication to access
2. **Audit Trail**: All signature access is logged via AuthMiddleware
3. **Flexibility**: Can still be used in document generation with proper auth
4. **Separation**: Clear distinction between public (profile pictures) and private (signatures) files
5. **Protection**: Double-layer protection (doc_type check + file type check)

## Files Modified
- `App/Services/UploadService.php` - Added 'signature' to allowed types, conditional URL generation
- `App/Controllers/Api/v1/UploadController.php` - Added doc_type blocking in getPublicFile()

## Testing

### Test 1: Upload Signature
```bash
curl -X POST "http://localhost:8000/api/v1/uploads" \
  -H "Authorization: Bearer TOKEN" \
  -F "file=@signature.png" \
  -F "doc_type=signature"

# Should return URL with /uploads/file/ (authenticated)
```

### Test 2: Access Signature with Auth
```bash
curl "http://localhost:8000/api/v1/uploads/file/10" \
  -H "Authorization: Bearer TOKEN"

# Should return the signature file
```

### Test 3: Try Public Access (Should Fail)
```bash
curl "http://localhost:8000/api/v1/uploads/public/10"

# Should return 403 Forbidden
```

### Test 4: Profile Picture Still Public
```bash
curl "http://localhost:8000/api/v1/uploads/public/5"

# Should return the profile picture (no auth needed)
```

## Recommendations

### For Document Signing System
1. Store signature upload_id in staff table
2. Fetch signature when generating documents
3. Embed signature image in PDF/document
4. Log signature usage for audit trail

### For Staff Profile
Consider adding a signature field to staff profile:
```json
{
  "staff_id": "STF001",
  "full_name": "Jane Smith",
  "signature": {
    "upload_id": 10,
    "url": "http://localhost:8000/api/v1/uploads/file/10",
    "uploaded_at": "2026-02-24 10:00:00"
  }
}
```
