# Training Class Scheduling

Staff schedule clinicians into training classes (e.g. Trach/Vent) held by an instructor at a branch, and track seats on the Calendars page.

## Language

**Class**:
A single dated session on the calendar, with a class type, branch, instructor, start time and capacity. A recurring series produces many Classes.
_Avoid_: Occurrence (code term only), event

**Class type**:
The category of training a Class teaches (e.g. Trach/Vent). Rescheduling never changes it.
_Avoid_: Class category, type

**Registration**:
A clinician's seat in one Class. Cancelled by soft-delete, moved by reschedule.
_Avoid_: Enrollment, request

**Capacity**:
The number of seats in a Class, shown as registered/total (e.g. 3/10). A Class at 10/10 is **Full** ("At Capacity").
_Avoid_: Slots

**Cancel registration**:
Removing one clinician from a Class, freeing a seat. Distinct from **Cancel Class**, which removes the whole Class.
_Avoid_: Unenroll

**Reschedule**:
Moving a Registration to another Class of the same class type and branch. The seat in the original Class is freed.
_Avoid_: Transfer

**Instructor**:
The user who teaches a Class and receives in-app notifications about its Registrations.

**Source tracker**:
The Recruiter Tracker a Registration was documented on (the new tracker from Add Clinician, or the tracker a next step was scheduled on). A Registration made from the Class Details pop-up has none.
_Avoid_: Linked tracker

**Clinician email**:
The Registered, Cancelled or Rescheduled email sent to a clinician when their Registration changes. Each one is recorded as a note on the clinician profile and the Source tracker.
_Avoid_: Booking email, confirmation (Bookings tool)
