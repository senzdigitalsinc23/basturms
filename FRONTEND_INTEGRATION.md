# Frontend Integration Guide

## Quick Start

### 1. Environment Setup
Ensure your frontend `.env.local` has:
```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1
API_KEY=devKey123
```

### 2. API Client Usage

The frontend already has an API client at `agh-validation-ui/lib/api.ts`. Here's how to use it:

```typescript
import { apiClient } from '@/lib/api';

// Login
const response = await apiClient.post('/validation/auth/login', {
  email: 'admin@validation.com',
  password: 'admin123'
});

// Store token
localStorage.setItem('token', response.token);

// Get staff (with auth)
const staff = await apiClient.get('/validation/staff');

// Validate staff
await apiClient.post('/validations', {
  staffIds: [1, 2, 3],
  month: 'March',
  year: 2026
});
```

## Important Changes from UUID to INT

### Before (UUID)
```typescript
interface Staff {
  id: string; // UUID like "550e8400-e29b-41d4-a716-446655440000"
  name: string;
  // ...
}
```

### After (INT)
```typescript
interface Staff {
  id: number; // Integer like 1, 2, 3
  name: string;
  // ...
}
```

### Update Your Types

If you have TypeScript interfaces in the frontend, update them:

```typescript
// agh-validation-ui/types/staff.ts (or wherever your types are)
export interface Staff {
  id: number;        // Changed from string to number
  name: string;
  email: string;
  role: 'staff' | 'incharge' | 'accountant' | 'admin';
  unitId: number;    // Changed from string to number
  unitName: string;
}

export interface Validation {
  id: number;        // Changed from string to number
  staffId: number;   // Changed from string to number
  month: string;
  year: number;
  validated: boolean;
  validatedBy: number | null;  // Changed from string to number
  validatedAt: string | null;
}
```

## API Response Examples

### Login Response
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@validation.com",
    "role": "admin",
    "unitId": 1,
    "unitName": "Finance"
  }
}
```

### Get Staff Response
```json
{
  "success": true,
  "staff": [
    {
      "id": 1,
      "name": "Admin User",
      "email": "admin@validation.com",
      "role": "admin",
      "unitId": 1,
      "unitName": "Finance"
    }
  ]
}
```

### Validate Staff Request
```json
{
  "staffIds": [1, 2, 3],
  "month": "March",
  "year": 2026
}
```

## Common Frontend Tasks

### 1. Display Staff List
```typescript
const [staff, setStaff] = useState<Staff[]>([]);

useEffect(() => {
  const fetchStaff = async () => {
    const response = await apiClient.get('/validation/staff');
    setStaff(response.staff);
  };
  fetchStaff();
}, []);

// Render
{staff.map(member => (
  <div key={member.id}>
    {member.name} - {member.role}
  </div>
))}
```

### 2. Validate Selected Staff
```typescript
const [selectedIds, setSelectedIds] = useState<number[]>([]);

const handleValidate = async () => {
  await apiClient.post('/validations', {
    staffIds: selectedIds,
    month: 'March',
    year: 2026
  });
  alert('Staff validated successfully!');
};

// Checkbox handler
const handleCheckbox = (id: number, checked: boolean) => {
  if (checked) {
    setSelectedIds([...selectedIds, id]);
  } else {
    setSelectedIds(selectedIds.filter(sid => sid !== id));
  }
};
```

### 3. Create New Staff (Simple)
```typescript
const handleCreateStaff = async (formData: any) => {
  const response = await apiClient.post('/validation/staff', {
    name: formData.name,
    email: formData.email,
    password: formData.password,
    unitId: parseInt(formData.unitId), // Ensure it's a number
    role: formData.role
  });
  
  console.log('Created staff with ID:', response.staff.id);
};
```

### 4. Create Comprehensive Staff
```typescript
const handleCreateComprehensiveStaff = async (formData: any) => {
  const response = await apiClient.post('/staff/comprehensive/create', {
    email: formData.email,
    password: formData.password,
    role: formData.role,
    personal_info: {
      title: formData.title,
      first_name: formData.firstName,
      last_name: formData.lastName,
      date_of_birth: formData.dob,
      gender: formData.gender,
      // ... more fields
    },
    contact_info: {
      primary_phone: formData.phone,
      residential_address: formData.address,
      // ... more fields
    },
    employment_info: {
      employee_number: formData.empNumber,
      staff_category: formData.category,
      employment_type: formData.type,
      date_of_first_appointment: formData.appointmentDate,
      unit_id: parseInt(formData.unitId),
      position_title: formData.position,
      // ... more fields
    }
  });
  
  console.log('Created comprehensive staff with ID:', response.staff_id);
};
```

## Error Handling

```typescript
try {
  const response = await apiClient.post('/validations', data);
  // Success
} catch (error: any) {
  if (error.response) {
    // API returned an error
    console.error('API Error:', error.response.data.message);
    alert(error.response.data.message);
  } else {
    // Network or other error
    console.error('Network Error:', error.message);
    alert('Failed to connect to server');
  }
}
```

## Testing Checklist

- [ ] Login works with test credentials
- [ ] Staff list displays correctly
- [ ] Staff IDs are numbers (not strings)
- [ ] Validation works with selected staff
- [ ] Create staff returns integer ID
- [ ] Unit selection works with integer IDs
- [ ] Filtering by month/year works
- [ ] Role-based access control works

## Common Issues & Solutions

### Issue: "Cannot read property 'id' of undefined"
**Solution:** Check that API responses are being parsed correctly and IDs are numbers.

### Issue: "Validation fails silently"
**Solution:** Check browser console for CORS errors. Ensure backend is running on port 8000.

### Issue: "Token expired"
**Solution:** JWT tokens expire after 24 hours. Implement token refresh or re-login.

### Issue: "Staff IDs not matching"
**Solution:** Ensure you're using integer IDs everywhere, not string UUIDs.

## Next Steps

1. Update all TypeScript interfaces to use `number` for IDs
2. Test login and staff listing
3. Test validation functionality
4. Implement comprehensive staff creation form (optional)
5. Add error handling and loading states
6. Test role-based access control

## Support

For API documentation, see:
- `VALIDATION_SYSTEM_API.md` - Complete API reference
- `COMPREHENSIVE_STAFF_API.md` - Comprehensive staff endpoints
- `MIGRATION_COMPLETE.md` - System overview
