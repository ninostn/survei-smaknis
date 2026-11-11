# MVP Features

## 1. Authentication

- Login with username and password.
- Logout.
- Role-based access: admin, teacher, student.
- Inactive accounts cannot log in.

## 2. User Management

- Admin can create, view, edit, and deactivate teacher accounts.
- Admin can create, view, edit, and deactivate student accounts.
- Deactivated accounts cannot log in or submit survey responses.
- Teacher and student accounts have separate profile tables.
- Admin does not require a teacher or student profile.

## 3. Academic Management

- Manage classes.
- Manage subjects.
- Manage teachers and students.
- Manage teaching assignments.
- Teaching assignments are required to validate which classes a teacher may target
  when creating a survey.
- Homeroom assignments are not part of the MVP.

## 4. Survey Management

- Admin and teachers can create surveys.
- Edit and delete draft surveys.
- Publish and close surveys.
- Assign survey categories.
- Configure student target: `none`, `all`, or selected classes.
- Configure teacher target: `none`, `all`, or selected teachers.
- At least one target must be active before a survey is published.
- Admin may target all students or any classes.
- Teachers may target only classes they teach through teaching assignments.
- Teachers cannot target all students.
- Admin and teachers may target all teachers or selected teachers.
- Survey scheduling uses the application timezone `Asia/Jakarta` (WIB). The
  `starts_at` and `ends_at` values entered in the form are local wall-clock times.

## 5. Dynamic Questions

Supported question types:

- Single choice
- Multiple choice
- Rating (1-5)
- Short text
- Long text

## 6. Survey Responses

- Students and teachers can fill eligible surveys.
- Admin cannot submit survey responses.
- Validate survey availability and respondent eligibility in the backend.
- Eligibility validation includes:
  - active account;
  - role matches an active survey target;
  - student or teacher profile exists;
  - selected student class or teacher target matches the user;
  - survey status is `published`;
  - current time is within `starts_at` and `ends_at` (inclusive);
  - user has not submitted a response for the survey.
- One response per user per survey.
- Validate answers according to question type.
- A teacher who created a survey may respond if the teacher matches the target.

## 7. Survey Results

- Admin can view all survey results.
- Survey creators can view their own survey results.
- Respondents can view published aggregate results.
- Respondents can view free-text answers only when the survey has at least 5 total responses.
- Free-text answers are hidden from respondents when total survey responses are fewer than 5.
- This threshold applies to all text questions in the survey.
- Choice and rating aggregates remain visible regardless of the threshold.
- Admin and survey creators can view all text answers regardless of response count.
- Do not expose individual respondent identities.
- Free-text answers must not be linked to respondent identity.

## Out of Scope

The following features are not part of the MVP:

- Email notifications
- PDF/Excel export
- File uploads in questions or answers
- Parent and alumni accounts
- Complex analytics
- Audit logs
- Mobile application
- Homeroom assignments

Do not implement out-of-scope features unless explicitly requested.
