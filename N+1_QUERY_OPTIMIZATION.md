# N+1 Query Optimization - Implementation Plan

**Date:** February 22, 2026  
**Status:** IN PROGRESS  
**Priority:** HIGH

---

## 🎯 Identified N+1 Issues

### 1. StudentPromotionService::bulkPromoteNormal() - CRITICAL
**Location:** `App/Services/StudentPromotionService.php` Line 115

**Problem:**
```php
foreach ($students as $studentIdentifier) {
    $studentNo = $this->promotionRepo->resolveStudentNo($studentIdentifier); // N+1
    $existingPromotion = $this->promotionRepo->hasBeenPromotedThisAcademicYear($studentNo); // N+1
    $current = $this->promotionRepo->getStudentCurrentClass($studentNo); // N+1
    $criteriaCheck = $this->checkPromotionCriteria($studentNo, $currentForStudent); // N+1
    $nextClass = $this->promotionRepo->getNextClass($currentForStudent); // N+1
}
```

**Impact:** For 100 students, this executes 500+ queries!

**Solution:** Batch fetch all data before the loop
```php
// Batch fetch student numbers
$studentNos = $this->promotionRepo->resolveStudentNosBatch($students);

// Batch fetch promotion status
$promotionStatus = $this->promotionRepo->hasBeenPromotedBatch($studentNos);

// Batch fetch current classes
$currentClasses = $this->promotionRepo->getStudentCurrentClassesBatch($studentNos);

// Batch fetch criteria checks
$criteriaChecks = $this->checkPromotionCriteriaBatch($studentNos, $currentClasses);

// Batch fetch next classes
$nextClasses = $this->promotionRepo->getNextClassesBatch(array_column($currentClasses, 'class_id'));

// Now loop with in-memory data
foreach ($students as $studentIdentifier) {
    $studentNo = $studentNos[$studentIdentifier] ?? null;
    // ... use pre-fetched data
}
```

**Expected Improvement:** 500+ queries → 5 queries (100x faster)

---

### 2. ClassSubjectService::bulkAssign() - HIGH
**Location:** `App/Services/ClassSubjectService.php` Line 40

**Problem:**
```php
foreach ($classIds as $classId) {
    if (!$this->classRepo->exists((int)$classId)) { // N+1
        // ...
    }
    foreach ($subjectIds as $subjectId) {
        if (!$this->subjectRepo->exists((int)$subjectId)) { // N+1
            // ...
        }
    }
}
```

**Impact:** For 10 classes × 20 subjects = 210 queries

**Solution:** Batch existence checks
```php
// Batch check class existence
$existingClasses = $this->classRepo->existsBatch($classIds);

// Batch check subject existence
$existingSubjects = $this->subjectRepo->existsBatch($subjectIds);

// Now loop with in-memory data
foreach ($classIds as $classId) {
    if (!isset($existingClasses[$classId])) {
        // ...
    }
    foreach ($subjectIds as $subjectId) {
        if (!isset($existingSubjects[$subjectId])) {
            // ...
        }
    }
}
```

**Expected Improvement:** 210 queries → 2 queries (105x faster)

---

### 3. AcademicSetupService::getAcademicYearsWithTerms() - MEDIUM
**Location:** `App/Services/AcademicSetupService.php` Line 223

**Problem:**
```php
foreach ($academicYears as $year) {
    $terms = $this->setupRepository->getTermsByAcademicYear($year['academic_year']); // N+1
    // ...
}
```

**Impact:** For 5 years = 6 queries (1 + 5)

**Solution:** Single query with JOIN
```php
// Fetch all years with terms in one query
$yearsWithTerms = $this->setupRepository->getAcademicYearsWithTermsJoined();
```

**Expected Improvement:** 6 queries → 1 query (6x faster)

---

### 4. SubjectService::bulkDelete() - LOW
**Location:** `App/Services/SubjectService.php` Line 166

**Problem:**
```php
foreach ($subjects as $idOrCode) {
    $subject = $this->repo->getByIdOrCode($idOrCode); // N+1
    // ...
}
```

**Impact:** For 50 subjects = 51 queries

**Solution:** Batch fetch subjects
```php
$subjects = $this->repo->getByIdsOrCodesBatch($subjectIds);
```

**Expected Improvement:** 51 queries → 1 query (51x faster)

---

## 📋 Implementation Priority

| Issue | Priority | Impact | Effort | Queries Saved |
|-------|----------|--------|--------|---------------|
| StudentPromotionService | CRITICAL | Very High | Medium | 500+ → 5 |
| ClassSubjectService | HIGH | High | Low | 210 → 2 |
| AcademicSetupService | MEDIUM | Medium | Low | 6 → 1 |
| SubjectService | LOW | Low | Low | 51 → 1 |

---

## 🚀 Implementation Steps

### Step 1: Create Batch Methods in Repositories

#### StudentPromotionRepository
```php
// Add these methods
public function resolveStudentNosBatch(array $identifiers): array
public function hasBeenPromotedBatch(array $studentNos): array
public function getStudentCurrentClassesBatch(array $studentNos): array
public function getNextClassesBatch(array $classIds): array
```

#### ClassRepository
```php
public function existsBatch(array $classIds): array
```

#### SubjectRepository
```php
public function existsBatch(array $subjectIds): array
public function getByIdsOrCodesBatch(array $identifiers): array
```

#### AcademicSetupRepository
```php
public function getAcademicYearsWithTermsJoined(): array
```

### Step 2: Refactor Services to Use Batch Methods

#### StudentPromotionService
- Refactor `bulkPromoteNormal()`
- Refactor `bulkPromoteSpecial()`
- Refactor `bulkGraduate()`

#### ClassSubjectService
- Refactor `bulkAssign()`
- Refactor `bulkRemove()`

#### AcademicSetupService
- Refactor `getAcademicYearsWithTerms()`

#### SubjectService
- Refactor `bulkDelete()`

### Step 3: Test and Verify

- Unit tests for batch methods
- Integration tests for services
- Performance benchmarks
- Verify data integrity

---

## 📊 Expected Performance Improvements

### Before Optimization
- StudentPromotionService (100 students): 500+ queries, ~5-10 seconds
- ClassSubjectService (10×20): 210 queries, ~2-3 seconds
- AcademicSetupService (5 years): 6 queries, ~100ms
- SubjectService (50 subjects): 51 queries, ~500ms

### After Optimization
- StudentPromotionService (100 students): 5 queries, ~50-100ms (100x faster)
- ClassSubjectService (10×20): 2 queries, ~20ms (100x faster)
- AcademicSetupService (5 years): 1 query, ~20ms (5x faster)
- SubjectService (50 subjects): 1 query, ~10ms (50x faster)

### Overall Impact
- **Queries Reduced:** 767 → 9 (98.8% reduction)
- **Response Time:** ~8 seconds → ~100ms (80x faster)
- **Database Load:** -98.8%
- **User Experience:** Dramatically improved

---

## 🎯 Success Criteria

- [ ] All batch methods created and tested
- [ ] All services refactored to use batch methods
- [ ] Unit tests passing
- [ ] Integration tests passing
- [ ] Performance benchmarks show expected improvements
- [ ] No data integrity issues
- [ ] Backward compatibility maintained

---

## 📝 Implementation Notes

### Best Practices
1. Always batch fetch before loops
2. Use IN clauses for batch queries
3. Index foreign keys for fast lookups
4. Cache batch results when appropriate
5. Handle empty arrays gracefully

### Common Patterns

**Pattern 1: Batch Existence Check**
```php
public function existsBatch(array $ids): array
{
    if (empty($ids)) return [];
    
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT id FROM table WHERE id IN ($placeholders)";
    $stmt = $this->db->prepare($sql);
    $stmt->execute($ids);
    
    return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id', 'id');
}
```

**Pattern 2: Batch Fetch with Mapping**
```php
public function getBatch(array $ids): array
{
    if (empty($ids)) return [];
    
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT * FROM table WHERE id IN ($placeholders)";
    $stmt = $this->db->prepare($sql);
    $stmt->execute($ids);
    
    $results = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $results[$row['id']] = $row;
    }
    return $results;
}
```

**Pattern 3: JOIN for Related Data**
```php
public function getWithRelations(): array
{
    $sql = "SELECT a.*, b.* 
            FROM table_a a
            LEFT JOIN table_b b ON a.id = b.a_id";
    $stmt = $this->db->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

---

## 🔗 Related Files

- `App/Services/StudentPromotionService.php`
- `App/Services/ClassSubjectService.php`
- `App/Services/AcademicSetupService.php`
- `App/Services/SubjectService.php`
- `App/Repositories/StudentPromotionRepository.php`
- `App/Repositories/ClassRepository.php`
- `App/Repositories/SubjectRepository.php`
- `App/Repositories/AcademicSetupRepository.php`

---

**Status:** READY TO IMPLEMENT  
**Estimated Time:** 4-6 hours  
**Expected Impact:** 80-100x performance improvement  
**Risk:** LOW (backward compatible)
