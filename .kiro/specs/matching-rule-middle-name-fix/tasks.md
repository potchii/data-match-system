# Implementation Plan

- [x] 1. Write bug condition exploration test
  - **Property 1: Fault Condition** - Middle Name Mismatch Causes False Negatives
  - **CRITICAL**: This test MUST FAIL on unfixed code - failure confirms the bug exists
  - **DO NOT attempt to fix the test or the code when it fails**
  - **NOTE**: This test encodes the expected behavior - it will validate the fix when it passes after implementation
  - **GOAL**: Surface counterexamples that demonstrate the bug exists
  - **Scoped PBT Approach**: Test concrete failing cases with matching first/last/DOB but different middle names
  - Test that ExactMatchRule matches records with same first_name, last_name, birthday but different middle_name values
  - Test that FullNameMatchRule matches records with same first_name, last_name but different middle_name values
  - Test cases: (1) middle_name present in one record but null in another, (2) different middle_name values in both records
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (this is correct - it proves the bug exists)
  - Document counterexamples found to understand root cause
  - Mark task complete when test is written, run, and failure is documented
  - _Requirements: 1.1, 1.2, 1.3, 1.4_

- [x] 2. Write preservation property tests (BEFORE implementing fix)
  - **Property 2: Preservation** - Other Matching Rules Continue Working
  - **IMPORTANT**: Follow observation-first methodology
  - Observe behavior on UNFIXED code for non-buggy inputs
  - Write property-based tests capturing observed behavior patterns:
    - PartialMatchWithDobRule continues to match on first + last + DOB (90% confidence)
    - FuzzyNameMatchRule continues to match on similar names (60-75% confidence)
    - Rule chain processes in priority order and stops at first match
    - Birthday comparison handles Carbon instances correctly
    - Records with no matches are classified as "NEW RECORD" (0% confidence)
  - Property-based testing generates many test cases for stronger guarantees
  - Run tests on UNFIXED code
  - **EXPECTED OUTCOME**: Tests PASS (this confirms baseline behavior to preserve)
  - Mark task complete when tests are written, run, and passing on unfixed code
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7_

- [x] 3. Fix matching rules to remove middle_name requirement

  - [x] 3.1 Fix ExactMatchRule - remove middle_name_normalized comparison
    - Remove `&& $candidate->middle_name_normalized === $normalizedData['middle_name_normalized']` from match condition
    - Keep only: last_name_normalized, first_name_normalized, and birthday comparisons
    - Maintain Carbon birthday conversion logic
    - _Bug_Condition: Record has matching first_name, last_name, birthday but different/missing middle_name_
    - _Expected_Behavior: ExactMatchRule SHALL match on first + last + DOB regardless of middle_name (100% confidence, "MATCHED")_
    - _Preservation: Other matching rules continue to work correctly_
    - _Requirements: 2.1, 2.3_

  - [x] 3.2 Fix FullNameMatchRule - remove middle_name_normalized comparison
    - Remove `&& $candidate->middle_name_normalized === $normalizedData['middle_name_normalized']` from match condition
    - Keep only: last_name_normalized and first_name_normalized comparisons
    - _Bug_Condition: Record has matching first_name, last_name but different/missing middle_name_
    - _Expected_Behavior: FullNameMatchRule SHALL match on first + last regardless of middle_name (80% confidence, "POSSIBLE DUPLICATE")_
    - _Preservation: Other matching rules continue to work correctly_
    - _Requirements: 2.2, 2.4_

  - [x] 3.3 Verify bug condition exploration test now passes
    - **Property 1: Expected Behavior** - Middle Name No Longer Required
    - **IMPORTANT**: Re-run the SAME test from task 1 - do NOT write a new test
    - The test from task 1 encodes the expected behavior
    - When this test passes, it confirms the expected behavior is satisfied
    - Run bug condition exploration test from step 1
    - **EXPECTED OUTCOME**: Test PASSES (confirms bug is fixed)
    - _Requirements: 2.1, 2.2, 2.3, 2.4_

  - [x] 3.4 Verify preservation tests still pass
    - **Property 2: Preservation** - Other Matching Rules Still Work
    - **IMPORTANT**: Re-run the SAME tests from task 2 - do NOT write new tests
    - Run preservation property tests from step 2
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)
    - Confirm all tests still pass after fix (no regressions)

- [x] 4. Evaluate PartialMatchWithDobRule redundancy
  - After fixing ExactMatchRule, both ExactMatchRule and PartialMatchWithDobRule check first + last + DOB
  - ExactMatchRule returns 100% confidence ("MATCHED")
  - PartialMatchWithDobRule returns 90% confidence ("MATCHED")
  - Since ExactMatchRule runs first in the rule chain, PartialMatchWithDobRule will never be reached for these cases
  - Consider removing PartialMatchWithDobRule from the rule chain to simplify the system
  - Document decision in code comments or update rule chain configuration
  - _Requirements: 2.5_

- [x] 5. Run full test suite to ensure no regressions
  - Run all existing matching rule tests
  - Run DataMatchService tests
  - Run ConfidenceScoreService tests
  - Verify no test failures or unexpected behavior changes
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7_

- [x] 6. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise
