# Signature as Base64 in Profile Response ✓

## Implementation
Staff signatures are now returned as base64-encoded data in the profile response, eliminating authentication issues when displaying signatures in the frontend.

## Database Changes

### Migration Created
- File: `Database/migrations/20260224000002_add_signature_to_staff.php`
- SQL: `add_signature_to_staff.sql`

### New Column in Staff Table
```sql
ALTER TABLE staff 
ADD COLUMN signature_id VARCHAR(100) NULL AFTER phone;

ALTER TABLE staff 
ADD INDEX idx_signature_id (signature_id);
```

## Profile Response

### Staff User Profile (with signature)
```json
{
  "success": true,
  "message": "Profile retrieved successfully",
  "data": {
    "id": 100,
    "user_id": "STF001",
    "username": "jane_teacher",
    "full_name": "Jane Mary Smith",
    "email": "jane@example.com",
    "phone": "0244000002",
    "role_id": 3,
    "role_name": "teacher",
    "status": "active",
    "is_super_admin": false,
    "created_at": "2025-01-15 10:00:00",
    "updated_at": "2026-02-24 03:00:00",
    "profile_picture_id": "STF001_abc123",
    "profile_picture": {
      "doc_id": "STF001_abc123",
      "upload_id": 10,
      "name": "photo.jpg",
      "url": "http://localhost:8000/api/v1/uploads/public/10",
      "type": "image/jpeg",
      "size": 42000,
      "uploaded_at": "2026-02-20 09:15:00"
    },
    "signature": {
      "doc_id": "STF001_sig123",
      "upload_id": 15,
      "type": "image/png",
      "base64": "iVBORw0KGgoAAAANSUhEUgAAAAUA..."
    }
  }
}
```

### Student User Profile (no signature)
```json
{
  "success": true,
  "message": "Profile retrieved successfully",
  "data": {
    "id": 2169,
    "user_id": "STU001",
    "username": "john_student",
    "full_name": "John Michael Doe",
    "email": "john@example.com",
    "phone": "0244000001",
    "role_id": 5,
    "role_name": "student",
    "status": "active",
    "is_super_admin": false,
    "profile_picture": { ... }
    // No signature field for students
  }
}
```

## How It Works

### 1. Upload Signature
```bash
curl -X POST "http://localhost:8000/api/v1/uploads" \
  -H "Authorization: Bearer TOKEN" \
  -F "file=@signature.png" \
  -F "doc_type=signature"
```

**Process:**
1. File uploaded to `storage/uploads/signatures/`
2. Record created in `uploads` table
3. `staff.signature_id` updated with doc_id
4. Returns URL (for reference, but base64 used in profile)

### 2. Fetch Profile
```bash
curl -X GET "http://localhost:8000/api/v1/profile/details" \
  -H "Authorization: Bearer TOKEN"
```

**Process:**
1. Query joins staff table with uploads table on signature_id
2. Reads signature file from disk
3. Converts to base64
4. Includes in profile response

### 3. Display Signature
```javascript
// React/Vue/Angular - Direct usage
<img src={`data:${user.signature.type};base64,${user.signature.base64}`} alt="Signature" />

// Or create object URL
const base64ToBlob = (base64, type) => {
  const binary = atob(base64);
  const array = new Uint8Array(binary.length);
  for (let i = 0; i < binary.length; i++) {
    array[i] = binary.charCodeAt(i);
  }
  return new Blob([array], { type });
};

const blob = base64ToBlob(user.signature.base64, user.signature.type);
const url = URL.createObjectURL(blob);
<img src={url} alt="Signature" />
```

## Query Updates

### ProfileService::getCurrentUser()
Now joins with uploads table to get signature:

```sql
LEFT JOIN staff sf ON u.user_id = sf.staff_id AND LOWER(r.name) != 'student'
LEFT JOIN uploads sig ON sf.signature_id = sig.doc_id AND LOWER(r.name) != 'student'
```

Selects signature fields:
- `sig.id as signature_upload_id`
- `sig.doc_id as signature_doc_id`
- `sig.url as signature_url`
- `sig.file_type as signature_type`

### ProfileService::sanitizeUserData()
Converts signature to base64:

```php
if (isset($userData['signature_url']) && !empty($userData['signature_url'])) {
    $signaturePath = $storagePath . str_replace('/', DIRECTORY_SEPARATOR, $userData['signature_url']);
    
    if (file_exists($signaturePath)) {
        $signatureContent = file_get_contents($signaturePath);
        $signatureBase64 = base64_encode($signatureContent);
    }
    
    $signature = [
        'doc_id' => $userData['signature_doc_id'],
        'upload_id' => (int)$userData['signature_upload_id'],
        'type' => $userData['signature_type'],
        'base64' => $signatureBase64
    ];
}
```

## Benefits

1. **No Auth Issues**: Base64 data embedded in profile response
2. **Single Request**: No separate request needed to fetch signature
3. **Browser Compatible**: Works directly in `<img>` tags with data URI
4. **Secure**: Only returned to authenticated users via profile endpoint
5. **Efficient**: Cached with profile data

## Frontend Usage

### Display Signature
```javascript
// Simple data URI
<img 
  src={`data:${user.signature.type};base64,${user.signature.base64}`} 
  alt="Signature" 
/>

// With fallback
{user.signature ? (
  <img 
    src={`data:${user.signature.type};base64,${user.signature.base64}`} 
    alt="Signature" 
  />
) : (
  <span>No signature uploaded</span>
)}
```

### Use in Document Generation
```javascript
// Signature is already base64, ready to embed in PDF
const signatureBase64 = user.signature.base64;
const signatureType = user.signature.type;

// Embed in PDF or document
pdf.addImage(
  `data:${signatureType};base64,${signatureBase64}`,
  'PNG',
  x, y, width, height
);
```

### Download Signature
```javascript
const downloadSignature = (signature) => {
  const link = document.createElement('a');
  link.href = `data:${signature.type};base64,${signature.base64}`;
  link.download = 'signature.png';
  link.click();
};
```

## Migration Steps

### Run the SQL Migration
```bash
mysql -h 127.0.0.1 -u root -p basturms_db < add_signature_to_staff.sql
```

Or manually:
```sql
USE basturms_db;

ALTER TABLE staff 
ADD COLUMN signature_id VARCHAR(100) NULL AFTER phone;

ALTER TABLE staff 
ADD INDEX idx_signature_id (signature_id);

DESCRIBE staff;
```

## Upload Flow

### 1. Staff Uploads Signature
```bash
POST /api/v1/uploads
- file: signature.png
- doc_type: signature
```

### 2. System Processing
1. Validates file (image, max 5MB)
2. Generates doc_id: `STF001_a3f5b2c8`
3. Saves to `storage/uploads/signatures/`
4. Creates record in `uploads` table
5. Updates `staff.signature_id = STF001_a3f5b2c8`

### 3. Staff Fetches Profile
```bash
GET /api/v1/profile/details
```

### 4. System Response
1. Joins staff with uploads on signature_id
2. Reads signature file from disk
3. Converts to base64
4. Returns in profile response

## Error Handling

### Signature File Not Found
If signature file is missing from disk:
- Logs warning
- Returns signature object with `base64: null`
- Doesn't break profile response

```json
{
  "signature": {
    "doc_id": "STF001_sig123",
    "upload_id": 15,
    "type": "image/png",
    "base64": null
  }
}
```

### No Signature Uploaded
If staff hasn't uploaded a signature:
- No signature field in response
- Profile response works normally

## Performance Considerations

### Base64 Size
- Original file: ~40KB
- Base64 encoded: ~53KB (33% larger)
- Acceptable for signatures (typically small images)

### Caching
- Profile response can be cached
- Signature included in cache
- Reduces repeated file reads

### Optimization Tips
1. Keep signature files small (<100KB)
2. Use PNG with transparency
3. Optimize images before upload
4. Consider lazy loading for lists

## Files Modified

1. **Database/migrations/20260224000002_add_signature_to_staff.php** - Migration class
2. **add_signature_to_staff.sql** - SQL migration script
3. **App/Services/ProfileService.php** - Updated getCurrentUser() and sanitizeUserData()
4. **App/Services/UploadService.php** - Added updateStaffSignature() method
5. **App/Controllers/Api/v1/UploadController.php** - Added signature update logic

## Testing

### Test Complete Flow

1. **Upload signature**:
```bash
curl -X POST "http://localhost:8000/api/v1/uploads" \
  -H "Authorization: Bearer TOKEN" \
  -F "file=@signature.png" \
  -F "doc_type=signature"
```

2. **Get profile**:
```bash
curl -X GET "http://localhost:8000/api/v1/profile/details" \
  -H "Authorization: Bearer TOKEN"
```

3. **Verify response includes**:
   - `signature.base64` field with encoded data
   - `signature.type` with MIME type
   - `signature.doc_id` and `signature.upload_id`

4. **Display in HTML**:
```html
<img src="data:image/png;base64,iVBORw0KGgo..." alt="Signature">
```

## Security Notes

- Signature base64 only returned to authenticated users
- Original file still secured in storage
- No public access to signature files
- Base64 data can't be accessed without valid session/token
- Signature files protected from direct URL access

## Backward Compatibility

- Works immediately after migration
- Existing staff without signatures: no signature field in response
- Existing uploads: work normally
- No breaking changes to other endpoints
