# Enhanced Fuzzy Matching Configuration Guide

## Overview

The Enhanced Fuzzy Matching feature is highly configurable through environment variables. This guide explains all available configuration options and how to tune them for your use case.

## Configuration Structure

All configuration is loaded from environment variables with sensible defaults. The configuration is validated on application startup.

## Environment Variables

### Feature Toggle

```bash
# Enable or disable enhanced fuzzy matching entirely
# Default: true
FUZZY_MATCHING_ENABLED=true
```

### Name Similarity Threshold

```bash
# Minimum similarity percentage for fuzzy name matching (0-100)
# Default: 85
# Lower values = more matches but more false positives
# Higher values = fewer matches but higher accuracy
FUZZY_MATCHING_NAME_THRESHOLD=85
```

### Address Similarity Threshold

```bash
# Minimum similarity percentage for fuzzy address matching (0-100)
# Default: 80
# Used for both address and template field fuzzy matching
FUZZY_MATCHING_ADDRESS_THRESHOLD=80
```

## Discriminator Configuration

### DOB (Date of Birth) Discriminator

```bash
# Enable/disable DOB discriminator
# Default: true
FUZZY_MATCHING_DOB_ENABLED=true

# Bonus for exact DOB match (0-100)
# Default: 10
FUZZY_MATCHING_DOB_BONUS_EXACT=10

# Penalty when DOB is missing from one record (0-100)
# Default: 5
FUZZY_MATCHING_DOB_PENALTY_MISSING=5

# Penalty for partial DOB match (same month/year, different day) (0-100)
# Default: 3
FUZZY_MATCHING_DOB_PENALTY_PARTIAL=3
```

### Gender Discriminator

```bash
# Enable/disable gender discriminator
# Default: true
FUZZY_MATCHING_GENDER_ENABLED=true

# Bonus for gender match (0-100)
# Default: 5
FUZZY_MATCHING_GENDER_BONUS=5

# Penalty when gender is missing from one record (0-100)
# Default: 3
FUZZY_MATCHING_GENDER_PENALTY_MISSING=3

# Reject match if genders don't match (true/false)
# Default: true
# If false, mismatches are allowed but don't contribute bonus
FUZZY_MATCHING_GENDER_REJECT_MISMATCH=true
```

### Address/Barangay Discriminator

```bash
# Enable/disable address/barangay discriminator
# Default: true
FUZZY_MATCHING_ADDRESS_ENABLED=true

# Bonus for exact barangay match (0-100)
# Default: 5
FUZZY_MATCHING_ADDRESS_BONUS_BARANGAY=5

# Bonus for fuzzy address match (0-100)
# Default: 5
FUZZY_MATCHING_ADDRESS_BONUS_FUZZY=5

# Penalty when address/barangay is missing from one record (0-100)
# Default: 5
FUZZY_MATCHING_ADDRESS_PENALTY_MISSING=5

# Penalty when address fuzzy match fails (0-100)
# Default: 5
FUZZY_MATCHING_ADDRESS_PENALTY_FUZZY_FAIL=5
```

### Template Fields Discriminator

```bash
# Enable/disable template field discriminator
# Default: true
FUZZY_MATCHING_TEMPLATE_ENABLED=true

# Bonus for exact template field match per field (0-100)
# Default: 2
FUZZY_MATCHING_TEMPLATE_BONUS_EXACT=2

# Bonus for fuzzy template field match per field (0-100)
# Default: 1
FUZZY_MATCHING_TEMPLATE_BONUS_FUZZY=1

# Penalty for template field no match per field (0-100)
# Default: 1
FUZZY_MATCHING_TEMPLATE_PENALTY_NO_MATCH=1

# Maximum total bonus from template fields (0-100)
# Default: 10
FUZZY_MATCHING_TEMPLATE_MAX_BONUS=10

# Maximum total penalty from template fields (0-100)
# Default: 5
FUZZY_MATCHING_TEMPLATE_MAX_PENALTY=5
```

### Base Confidence Scores

```bash
# Base confidence score for fuzzy name match (0-100)
# Default: 70
FUZZY_MATCHING_BASE_CONFIDENCE=70

# Maximum possible confidence score (0-100)
# Default: 100
FUZZY_MATCHING_MAX_CONFIDENCE=100

# Minimum possible confidence score (0-100)
# Default: 0
FUZZY_MATCHING_MIN_CONFIDENCE=0
```

## Configuration Examples

### Conservative Configuration (High Accuracy, Fewer Matches)

```bash
# Stricter thresholds
FUZZY_MATCHING_NAME_THRESHOLD=90
FUZZY_MATCHING_ADDRESS_THRESHOLD=85

# Higher bonuses for discriminators
FUZZY_MATCHING_DOB_BONUS_EXACT=15
FUZZY_MATCHING_GENDER_BONUS=10
FUZZY_MATCHING_ADDRESS_BONUS_BARANGAY=10
FUZZY_MATCHING_ADDRESS_BONUS_FUZZY=10

# Stricter penalties
FUZZY_MATCHING_DOB_PENALTY_MISSING=10
FUZZY_MATCHING_GENDER_PENALTY_MISSING=10
FUZZY_MATCHING_ADDRESS_PENALTY_MISSING=10

# Reject on gender mismatch
FUZZY_MATCHING_GENDER_REJECT_MISMATCH=true
```

### Aggressive Configuration (More Matches, Higher False Positives)

```bash
# Looser thresholds
FUZZY_MATCHING_NAME_THRESHOLD=75
FUZZY_MATCHING_ADDRESS_THRESHOLD=70

# Lower bonuses for discriminators
FUZZY_MATCHING_DOB_BONUS_EXACT=5
FUZZY_MATCHING_GENDER_BONUS=2
FUZZY_MATCHING_ADDRESS_BONUS_BARANGAY=2
FUZZY_MATCHING_ADDRESS_BONUS_FUZZY=2

# Lighter penalties
FUZZY_MATCHING_DOB_PENALTY_MISSING=2
FUZZY_MATCHING_GENDER_PENALTY_MISSING=1
FUZZY_MATCHING_ADDRESS_PENALTY_MISSING=2

# Allow gender mismatches
FUZZY_MATCHING_GENDER_REJECT_MISMATCH=false
```

### Balanced Configuration (Default)

```bash
# Moderate thresholds
FUZZY_MATCHING_NAME_THRESHOLD=85
FUZZY_MATCHING_ADDRESS_THRESHOLD=80

# Moderate bonuses
FUZZY_MATCHING_DOB_BONUS_EXACT=10
FUZZY_MATCHING_GENDER_BONUS=5
FUZZY_MATCHING_ADDRESS_BONUS_BARANGAY=5
FUZZY_MATCHING_ADDRESS_BONUS_FUZZY=5

# Moderate penalties
FUZZY_MATCHING_DOB_PENALTY_MISSING=5
FUZZY_MATCHING_GENDER_PENALTY_MISSING=3
FUZZY_MATCHING_ADDRESS_PENALTY_MISSING=5

# Reject on gender mismatch
FUZZY_MATCHING_GENDER_REJECT_MISMATCH=true
```

### Discriminator-Focused Configuration

```bash
# Emphasize discriminators over name similarity
FUZZY_MATCHING_NAME_THRESHOLD=80

# High bonuses for discriminators
FUZZY_MATCHING_DOB_BONUS_EXACT=20
FUZZY_MATCHING_GENDER_BONUS=15
FUZZY_MATCHING_ADDRESS_BONUS_BARANGAY=15
FUZZY_MATCHING_ADDRESS_BONUS_FUZZY=10

# High penalties for missing discriminators
FUZZY_MATCHING_DOB_PENALTY_MISSING=15
FUZZY_MATCHING_GENDER_PENALTY_MISSING=10
FUZZY_MATCHING_ADDRESS_PENALTY_MISSING=15

# Strict gender matching
FUZZY_MATCHING_GENDER_REJECT_MISMATCH=true
```

### Name-Focused Configuration

```bash
# Emphasize name similarity over discriminators
FUZZY_MATCHING_NAME_THRESHOLD=80

# Low bonuses for discriminators
FUZZY_MATCHING_DOB_BONUS_EXACT=3
FUZZY_MATCHING_GENDER_BONUS=2
FUZZY_MATCHING_ADDRESS_BONUS_BARANGAY=2
FUZZY_MATCHING_ADDRESS_BONUS_FUZZY=2

# Low penalties for missing discriminators
FUZZY_MATCHING_DOB_PENALTY_MISSING=2
FUZZY_MATCHING_GENDER_PENALTY_MISSING=1
FUZZY_MATCHING_ADDRESS_PENALTY_MISSING=2

# Allow gender mismatches
FUZZY_MATCHING_GENDER_REJECT_MISMATCH=false
```

## Configuration Validation

The configuration is validated on application startup. Invalid configurations will throw an `InvalidArgumentException` with a clear error message.

### Validation Rules

1. All numeric values must be between 0 and 100
2. Bonuses and penalties must be positive
3. Thresholds must be between 0 and 100
4. Enabled flags must be boolean
5. min_confidence must not exceed base_confidence
6. base_confidence must not exceed max_confidence

### Example Validation Error

```
Configuration error: discriminators.dob.bonus_exact_match must be between 0 and 100, got 150
```

## Configuration Loading

Configuration is loaded in this priority order:

1. Environment variables (highest priority)
2. Config file (if exists)
3. Hardcoded defaults (lowest priority)

### Environment Variable Naming Convention

All environment variables follow this pattern:

```
FUZZY_MATCHING_[DISCRIMINATOR]_[SETTING]
```

Examples:
- `FUZZY_MATCHING_DOB_ENABLED`
- `FUZZY_MATCHING_GENDER_BONUS`
- `FUZZY_MATCHING_ADDRESS_PENALTY_MISSING`

## Tuning Guide

### Increase Matches

1. Lower `FUZZY_MATCHING_NAME_THRESHOLD` (e.g., 80 instead of 85)
2. Lower `FUZZY_MATCHING_ADDRESS_THRESHOLD` (e.g., 75 instead of 80)
3. Reduce penalty values
4. Set `FUZZY_MATCHING_GENDER_REJECT_MISMATCH=false`

### Decrease False Positives

1. Raise `FUZZY_MATCHING_NAME_THRESHOLD` (e.g., 90 instead of 85)
2. Raise `FUZZY_MATCHING_ADDRESS_THRESHOLD` (e.g., 85 instead of 80)
3. Increase bonus values for discriminators
4. Increase penalty values for missing discriminators
5. Set `FUZZY_MATCHING_GENDER_REJECT_MISMATCH=true`

### Emphasize Specific Discriminators

1. Increase bonus for that discriminator
2. Increase penalty for missing that discriminator
3. Disable other discriminators if not needed

### Disable Specific Discriminators

Set the `_ENABLED` flag to false:

```bash
FUZZY_MATCHING_DOB_ENABLED=false
FUZZY_MATCHING_GENDER_ENABLED=false
FUZZY_MATCHING_ADDRESS_ENABLED=false
FUZZY_MATCHING_TEMPLATE_ENABLED=false
```

## Monitoring Configuration

The configuration is logged on application startup:

```
[INFO] Fuzzy Matching Configuration Loaded
- enabled: true
- name_similarity_threshold: 85
- address_similarity_threshold: 80
- discriminators:
  - dob_enabled: true
  - gender_enabled: true
  - address_enabled: true
  - template_fields_enabled: true
```

## Performance Impact

Configuration changes can impact performance:

- Lower thresholds = more candidates evaluated = slower matching
- More discriminators enabled = more validation = slower matching
- Higher penalties = more rejections = faster matching

Monitor performance metrics when tuning configuration.

## Best Practices

1. Start with default configuration
2. Monitor false positive and false negative rates
3. Adjust thresholds incrementally
4. Test with representative data
5. Monitor performance metrics
6. Document your configuration choices
7. Use environment-specific configurations (dev, staging, prod)
