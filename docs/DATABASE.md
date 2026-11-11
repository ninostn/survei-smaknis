# Database Design

## MVP Table Inventory

The MVP uses the following 15 tables:

1. `users`
2. `students`
3. `teachers`
4. `classes`
5. `subjects`
6. `teaching_assignments`
7. `survey_categories`
8. `surveys`
9. `survey_target_classes`
10. `survey_target_teachers`
11. `questions`
12. `question_options`
13. `responses`
14. `answers`
15. `answer_options`

`homeroom_assignments` is intentionally excluded from the MVP. It is not part of the
current schema and must not be created unless the scope is changed.

## Authentication

### users

- id
- username (unique)
- password
- role (admin, teacher, student)
- is_active (boolean, default true)
- remember_token — technical column for Laravel's "remember me" session
  persistence. Kept as standard framework support; the MVP does not need to
  expose or rely on it.
- timestamps

All users authenticate using username and password. Email-based password reset
is not part of the MVP: the `users` table has no email column and no
`password_reset_tokens` table is kept.

Additional rules:

- Inactive users cannot log in.
- Inactive users cannot submit survey responses.
- Admin does not require a teacher or student profile.
- `is_active` is required by the account deactivation feature and the eligibility rules.

### students

- id
- user_id (unique, foreign key to users.id)
- nis (unique)
- name
- class_id (foreign key to classes.id)
- timestamps

### teachers

- id
- user_id (unique, foreign key to users.id)
- nip (nullable, unique)
- name
- timestamps

## Academic Data

### classes

- id
- name
- academic_year
- is_active
- timestamps

Unique: `name` + `academic_year`.

### subjects

- id
- code (unique)
- name
- timestamps

Unique: `code`. Subject codes must not be duplicated.

### teaching_assignments

- id
- teacher_id (foreign key to teachers.id)
- class_id (foreign key to classes.id)
- subject_id (foreign key to subjects.id)
- academic_year
- timestamps

Unique: `teacher_id` + `class_id` + `subject_id` + `academic_year`.

Purpose: `teaching_assignments` is part of the MVP because it defines which classes a
teacher is allowed to target when creating a student-targeted survey.

`homeroom_assignments` is not part of the MVP.

## Survey Data

### survey_categories

- id
- name
- description
- timestamps

### surveys

- id
- creator_id (foreign key to users.id)
- category_id (foreign key to survey_categories.id)
- title
- description
- student_target_mode (none, all, classes)
- teacher_target_mode (none, all, selected)
- status (draft, published, closed)
- starts_at
- ends_at
- is_result_published
- timestamps

Rules:

- `teaching_assignment_id` is removed from the survey schema. It is not used by the
  target mechanism.
- At least one of `student_target_mode` or `teacher_target_mode` must be active
  (`all`, `classes`, or `selected`).
- `student_target_mode = classes` requires at least one row in
  `survey_target_classes`.
- `student_target_mode = all` or `none` must not have rows in
  `survey_target_classes`.
- `teacher_target_mode = selected` requires at least one row in
  `survey_target_teachers`.
- `teacher_target_mode = all` or `none` must not have rows in
  `survey_target_teachers`.
- A survey is only eligible to be filled when its status is `published` and the
  current time is between `starts_at` and `ends_at`. Both dates must be present for
  the survey to be eligible.
- The target modes and target pivot rows must be validated in the backend, not only
  in the UI.

### survey_target_classes

- id
- survey_id (foreign key to surveys.id)
- class_id (foreign key to classes.id)
- timestamps

Unique: `survey_id` + `class_id`.

Foreign key behavior: deleting a survey deletes its target-class rows. Deleting a
class is restricted while it is referenced by a survey target.

### survey_target_teachers

- id
- survey_id (foreign key to surveys.id)
- teacher_id (foreign key to teachers.id)
- timestamps

Unique: `survey_id` + `teacher_id`.

Foreign key behavior: deleting a survey deletes its target-teacher rows. Deleting a
teacher is restricted while it is referenced by a survey target.

No separate target-role table is needed. Student and teacher targeting is controlled
directly by the two target-mode columns on `surveys`.

## Question Data

### questions

- id
- survey_id (foreign key to surveys.id)
- question_text
- type (single_choice, multiple_choice, rating, short_text, long_text)
- is_required
- position
- timestamps

### question_options

- id
- question_id (foreign key to questions.id)
- option_text
- position
- timestamps

## Response Data

### responses

- id
- survey_id (foreign key to surveys.id)
- user_id (foreign key to users.id)
- submitted_at
- timestamps

Unique: `survey_id` + `user_id`.

### answers

- id
- response_id (foreign key to responses.id)
- question_id (foreign key to questions.id)
- answer_text (nullable)
- rating_value (nullable)
- timestamps

Unique: `response_id` + `question_id`.

### answer_options

- id
- answer_id (foreign key to answers.id)
- question_id (foreign key to questions.id)
- option_id (foreign key to question_options.id)
- timestamps

Unique: `answer_id` + `option_id`.

## Timezone

- The application timezone is `Asia/Jakarta` (`UTC+07:00`), set in
  `config/app.php`.
- The MySQL connection sets its session timezone to `+07:00` in
  `config/database.php`. **Both values must always match.** MySQL converts every
  `TIMESTAMP` column using the session timezone, so a mismatch shifts stored
  instants.
- All time columns (`surveys.starts_at`, `surveys.ends_at`,
  `responses.submitted_at`, and all `created_at`/`updated_at`) are `TIMESTAMP`.
- `datetime-local` input is treated as local wall-clock time (WIB) with no
  browser-side conversion; the server parses it in the application timezone.
- Time comparisons such as `starts_at <= now <= ends_at` are inclusive and made
  in the application timezone.
- Do not add `DB_TIMEZONE` or change either timezone without changing the other
  in the same change.

## Entity Relationships

- `users` has one optional `students` profile and one optional `teachers` profile.
- `classes` has many `students` and many `teaching_assignments`.
- `teachers` has many `teaching_assignments`.
- `subjects` has many `teaching_assignments`.
- `surveys` belongs to a creator (`users`) and a category (`survey_categories`).
- `surveys.creator_id` references `users.id`. There is no foreign key from
  `surveys` to `teachers.id`.
- A teacher's created surveys are accessed through
  `Teacher → User → createdSurveys`, because `users` holds the account and
  `teachers` is only a profile.
- `surveys` has many `questions`, `survey_target_classes`,
  `survey_target_teachers`, and `responses`.
- `questions` has many `question_options` and `answers`.
- `responses` has many `answers`.
- `answers` has many `answer_options`.

## Survey Target Eligibility

Eligibility must be enforced by the backend before a response is accepted.

### Student Eligibility

A user may fill a survey as a student only when all of the following are true:

- The user has the `student` role.
- The account is active.
- The student profile exists.
- `student_target_mode = all`: the student is eligible.
- `student_target_mode = classes`: the student's current class is listed in
  `survey_target_classes`.
- The current class at the time of submission is used for eligibility.

A teacher who creates a survey may only select classes that the teacher teaches
through `teaching_assignments`. Admin may select all classes.

### Teacher Eligibility

A user may fill a survey as a teacher only when all of the following are true:

- The user has the `teacher` role.
- The account is active.
- The teacher profile exists.
- `teacher_target_mode = all`: the teacher is eligible.
- `teacher_target_mode = selected`: the teacher is listed in
  `survey_target_teachers`.

Admin and teachers may target all teachers or selected teachers. A teacher who
created a survey may also respond if the teacher matches the survey target.

### Admin Eligibility

Admin must not submit responses to any survey.

Admin may still create surveys, manage targets, and view results according to the
result-access rules.

### General Eligibility Rules

Before accepting a response, the backend must validate:

1. The account is active.
2. The user has a role that matches an active survey target.
3. The student or teacher profile exists.
4. The user matches the configured target.
5. The survey status is `published`.
6. The current time is within `starts_at` and `ends_at`.
7. The user has not submitted a response for the survey.

The `responses` table must keep the unique constraint on `survey_id` + `user_id` to
prevent duplicate responses.

## Survey Creation Authorization

| Capability | Admin | Teacher |
| --- | --- | --- |
| Target all students | Yes | No |
| Target selected classes | All classes | Classes the teacher teaches |
| Target all teachers | Yes | Yes |
| Target selected teachers | Yes | Yes |
| View own survey results | Yes | Yes |
| View all school results | Yes | No |

## Result Access

- Admin can view all survey results.
- Survey creators can view their own survey results.
- Respondents can view published aggregate results.
- Survey results must not expose individual respondent identities.
- Free-text answers must not be linked to respondent identity in any result view.

## Important Rules

- Responses belong to users, not only students, because teachers can also answer
  surveys.
- Each user can submit only one response per survey.
- Answer options must belong to the same question as the answer.
- Survey results must not expose respondent identities.
- Enforce valid answer types in backend validation.
- Use database transactions when saving survey responses involving multiple tables.
- Target modes and target pivot rows must remain consistent.
- Deleting a survey may delete its target pivot rows, questions, options, and related
  response data according to the defined foreign-key behavior.
- Master data referenced by historical survey data should be restricted from deletion
  where appropriate.
