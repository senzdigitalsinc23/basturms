# Profile Picture Overwrite Feature ✓

## Requirement
When a user uploads a second profile picture, the existing one should be overwritten to avoid duplication.

## Solution Implemented

### Automatic Cleanup on Upload
When uploading a new profile picture, the system now:
1. Checks if user has an existing profile picture
2. Deletes the old file from disk
3. Removes the old database record
4. Uploads and saves the new profile picture

### Implementation Details

#### UploadController.php
Added check before upload:
```php
// If this is a profile picture, delete the old one first
if ($docType === 'profile_picture') {
    $this->uploadService->deleteOldProfilePicture($userPrimaryId);
}
```

#### UploadService.php
New method `deleteOldProfilePicture()`:
```php
public function deleteOldProfilePicture(int $userId): bool
{
    // 1. Get user's current profile_picture_id
    // 2. Find the upload record by doc_id
    // 3. Delete physical file from storage/uploads/profile_pictures/
    // 4. Delete database record from uploads table
    // 5. Return success (doesn't fail upload if deletion fails)
}
```

## Behavior

### First Upload
```
User has no profile picture
→ Upload new image
→ Save to database and disk
→ Update user.profile_picture_id
```

### Second Upload (Overwrite)
```
User has existing profile picture
→ Delete old file from disk
→ Delete old database record
→ Upload new image
→ Save to database and disk
→ Update user.profile_picture_id
```

### Error Handling
- If old file doesn't exist on disk: Continues with upload
- If database deletion fails: Logs error but continues with upload
- If new upload fails: Old picture remains deleted (user can re-upload)

## Benefits

1. **No Duplication**: Only one profile picture per user at any time
2. **Storage Efficiency**: Old files are automatically cleaned up
3. **Database Cleanliness**: No orphaned upload records
4. **Seamless UX**: Users can change profile picture without manual deletion

## Database Impact

### Before (with duplicates)
```
uploads table:
- id: 1, doc_id: user1_abc123, doc_type: profile_picture
- id: 2, doc_id: user1_def456, doc_type: profile_picture  ← duplicate
- id: 3, doc_id: user1_ghi789, doc_type: profile_picture  ← duplicate

users table:
- id: 1, profile_picture_id: user1_ghi789  ← only references latest
```

### After (no duplicates)
```
uploads table:
- id: 3, doc_id: user1_ghi789, doc_type: profile_picture  ← only one

users table:
- id: 1, profile_picture_id: user1_ghi789
```

## Testing

### Test Scenario 1: First Upload
```bash
# Upload first profile picture
curl -X POST "http://localhost:8000/api/v1/uploads" \
  -H "Authorization: Bearer TOKEN" \
  -F "file=@image1.jpg" \
  -F "doc_type=profile_picture"

# Result: File saved, database record created
```

### Test Scenario 2: Second Upload (Overwrite)
```bash
# Upload second profile picture
curl -X POST "http://localhost:8000/api/v1/uploads" \
  -H "Authorization: Bearer TOKEN" \
  -F "file=@image2.jpg" \
  -F "doc_type=profile_picture"

# Result: 
# - Old file deleted from storage/uploads/profile_pictures/
# - Old database record deleted
# - New file saved
# - New database record created
```

### Verification
```bash
# Check profile details
curl -X GET "http://localhost:8000/api/v1/profile/details" \
  -H "Authorization: Bearer TOKEN"

# Should return only the latest profile picture
```

## Files Modified
- `App/Controllers/Api/v1/UploadController.php` - Added deleteOldProfilePicture() call before upload
- `App/Services/UploadService.php` - Added deleteOldProfilePicture() method

## Edge Cases Handled

1. **User has no existing profile picture**: Deletion is skipped, upload proceeds normally
2. **Old file doesn't exist on disk**: Deletion continues, removes database record only
3. **Database deletion fails**: Error logged, upload continues (prevents blocking user)
4. **New upload fails**: Old picture is deleted, user can retry upload

## Future Enhancements

Consider adding:
- Soft delete (mark as deleted instead of physical deletion)
- File versioning (keep history of profile pictures)
- Rollback capability if new upload fails
- Admin view of deleted files
