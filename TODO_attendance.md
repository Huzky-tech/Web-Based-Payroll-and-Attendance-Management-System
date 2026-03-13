# Attendance Tracking Dashboard - Implementation Plan

## Information Gathered
**Existing**:
- `admin/attendance.php`: Basic HTML skeleton (header, hardcoded filters, 3 demo cards, static table with 3 rows)
- `css/attendance.css`: Good foundation (CSS vars, cards, table, status pills, responsive grid)
- `js/attendance.js`: Advanced JS class with API integration (loadStats, loadSiteAttendance, clockIn/out, search/filter)
- APIs: get_attendance.php, get_attendance_stats.php, clock_in/out.php exist

**Gaps**: Hardcoded data, needs full dynamic + filters (site/dept/supervisor/date), export CSV, supervisor filter, full responsive.

## Plan
1. **UI Enhancements** `admin/attendance.php`
   - Dynamic filters (dropdowns populated from APIs)
   - Real-time date picker binding
   - Export button CSV logic
   - User controls (profile dropdown)

2. **JS Overhaul** `js/attendance.js`
   - Load filter options (sites/depts/supervisors)
   - Multi-filter support (site+date+dept)
   - Export CSV function
   - Project card click → filter table

3. **CSS Polish** `css/attendance.css`
   - Dropdown styles
   - Export button hover
   - Mobile responsive (stack cards/table)

4. **Sample Data** `database/sample_attendance.sql`

## Dependent Files
- `admin/attendance.php` (rewrite HTML)
- `js/attendance.js` (enhance filters/export)
- `css/attendance.css` (add styles)
- New: `api/get_attendance_filters.php` (sites/depts/supervisors)

## Followup
Test: Load page → see cards/table → filter works → export CSV → responsive

Approve plan?

