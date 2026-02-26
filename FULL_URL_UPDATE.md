# Profile Picture Full URL Implementation ✓

## Requirement
Frontend needs full web URLs to access profile pictures on the server, not relative paths.

## Solution Implemented

### 1. Upload Response - Returns Full URL
When uploading a file via `/api/v1/uploads`:

**Response now includes**:
```json
{
  "success": true,
  "upload_id": 123,
  "url": "http://localhost:8000/api/v1/uploads/file/123",
  "doc_name": "profile.jpg"
}
```

The URL points to the file serving endpoint that securely delivers the file.

### 2. Profile Details - Returns Full URL
When fetching profile via `/api/v1/profile/details`:

**Response includes**:
```json
{
  "success": true,
  "message": "Profile retrieved successfully",
  "data": {
    "id": 1,
    "user_id": "user1",
    "username": "john_doe",
    "email": "john@example.com",
    "profile_picture_id": "user1_a3f5b2c8",
    "profile_picture": {
      "doc_id": "user1_a3f5b2c8",
      "upload_id": 123,
      "name": "profile.jpg",
      "url": "http://localhost:8000/api/v1/uploads/file/123",
      "type": "image/jpeg",
      "size": 45678,
      "uploaded_at": "2024-01-15 10:30:00"
    }
  }
}
```

## Implementation Details

### UploadService.php Changes

1. **Database Storage**: Still stores relative paths (`uploads/profile_pictures/filename.jpg`)
   - Keeps file system operations simple
   - Maintains backward compatibility

2. **Response URL**: Returns full URL to file serving endpoint
   ```php
   $appUrl = $_ENV['APP_URL'] ?? 'http://localhost:8000';
   $fullUrl = $appUrl . '/api/v1/uploads/file/' . $uploadId;
   ```

### ProfileService.php Changes

1. **sanitizeUserData()**: Generates full URL when returning profile data
   ```php
   $appUrl = $_ENV['APP_URL'] ?? 'http://localhost:8000';
   $fullUrl = $appUrl . '/api/v1/uploads/file/' . $userData['profile_picture_upload_id'];
   ```

## Benefits

1. **Frontend Ready**: URLs can be used directly in `<img>` tags
2. **Secure**: Files served through controlled endpoint with authentication
3. **Flexible**: APP_URL can be changed in .env for different environments
4. **Consistent**: Same URL format for all file types

## Configuration

Set your application URL in `.env`:
```env
APP_URL=http://localhost:8000
# or for production:
APP_URL=https://yourdomain.com
```

## Frontend Usage

### Display Profile Picture
```javascript
// React/Vue/Angular example
<img src={user.profile_picture.url} alt={user.username} />

// Direct URL usage
fetch(user.profile_picture.url)
  .then(response => response.blob())
  .then(blob => {
    const imageUrl = URL.createObjectURL(blob);
    // Use imageUrl
  });
```

### Upload Profile Picture
```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('doc_type', 'profile_picture');

fetch('http://localhost:8000/api/v1/uploads', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token
  },
  body: formData
})
.then(response => response.json())
.then(data => {
  console.log('File URL:', data.url);
  // Refresh profile to get updated data
});
```

## File Serving Endpoint

The `/api/v1/uploads/file/{id}` endpoint:
- Validates file exists in database
- Checks file exists on disk
- Sets appropriate Content-Type headers
- Streams file content to client
- Can be extended with authentication/authorization

## Files Modified
- `App/Services/UploadService.php` - Updated upload() method to return full URL
- `App/Services/ProfileService.php` - Updated sanitizeUserData() to generate full URLs

## Testing

1. **Upload a file**:
   ```bash
   curl -X POST "http://localhost:8000/api/v1/uploads" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -F "file=@image.jpg" \
     -F "doc_type=profile_picture"
   ```
   
   Should return: `{"success": true, "url": "http://localhost:8000/api/v1/uploads/file/123", ...}`

2. **Get profile details**:
   ```bash
   curl -X GET "http://localhost:8000/api/v1/profile/details" \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```
   
   Should return profile with full URL in `profile_picture.url`

3. **Access the file**:
   ```bash
   curl "http://localhost:8000/api/v1/uploads/file/123" --output image.jpg
   ```
   
   Should download the actual image file
