# AICode Module Database Error - Fix Summary

## Problem

When clicking "Save and display" for the AICode activity, you received:

- **Error**: "Table 'aicode' does not exist"
- **Error code**: `dbtableenotexist`

## Root Cause

In Moodle, every activity module **must have a main database table** with the **same name as the module**.

The AICode module had its main table named `aicode_problems` instead of just `aicode`. Moodle's core expects a table named `aicode` for the `mod_aicode` module.

## What Was Fixed

### 1. **Database Schema** (`db/install.xml`)

- ✅ Renamed main table from `aicode_problems` to `aicode`
- ✅ Updated foreign key reference in `aicode_attempts` table

### 2. **Code Updates** (`lib.php`)

- ✅ Updated `aicode_add_instance()` to use `aicode` table
- ✅ Updated `aicode_update_instance()` to use `aicode` table
- ✅ Updated `aicode_delete_instance()` to use `aicode` table
- ✅ Added proper field filtering (only saves fields that exist in database)
- ✅ Added default values for optional fields
- ✅ Added better JSON validation for test cases
- ✅ Added error handling for grading functions

### 3. **Event & External Classes**

- ✅ Updated `classes/event/course_module_viewed.php`
- ✅ Updated `classes/external/run_code.php`
- ✅ Updated `classes/privacy/provider.php`
- ✅ Updated `view.php`

### 4. **Upgrade Script** (`db/upgrade.php`)

- ✅ Created upgrade script to rename existing table
- ✅ Updated module version from `2025041400` to `2025041401`
- ✅ Successfully migrated existing data

## Verification

The fix has been verified and:

- ✅ Table `aicode` exists with correct structure
- ✅ Old table `aicode_problems` has been removed
- ✅ Foreign keys are properly updated
- ✅ Module version updated successfully

## How to Test

1. Go to your Moodle course
2. Turn editing on
3. Click "Add an activity or resource"
4. Select "AICode"
5. Fill in the form:
   - Name: Your problem name
   - Description: Problem description
   - Test cases: `[]` or valid JSON
   - Starter code: Optional template code
6. Click **"Save and display"**

**Result**: Should save successfully without errors! ✅

## Files Changed

- `db/install.xml` - Database schema
- `db/upgrade.php` - New upgrade script
- `lib.php` - Core module functions
- `view.php` - View script
- `classes/event/course_module_viewed.php` - Event class
- `classes/external/run_code.php` - External API
- `classes/privacy/provider.php` - Privacy API
- `version.php` - Version bump

## Technical Notes

- **Moodle Requirement**: Main table must match module name
- **Table naming**: `mod_aicode` → table must be `mdl_aicode` (or `aicode` without prefix)
- **Version bump**: Required for upgrade script to run
- **Foreign keys**: Must be updated when renaming referenced tables

---

**Status**: ✅ **FIXED**
**Date**: October 13, 2025
