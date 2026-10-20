# TCGC real data setup and client demonstration guide

This guide explains how to use the system with an authorized employee roster and paper questionnaire responses, and how to demonstrate each feature to the client. The original manuscript remains the research reference. The existing demonstration database is preserved. Real data has not yet been transcribed or imported.

## 1 Start the existing demonstration

Start MySQL in XAMPP. Open PowerShell:

```powershell
cd C:\xampp\htdocs\tcgc_erdss
php -v
php artisan serve
```

Use PHP 8.4.1 or newer. This computer's `php` is PHP 8.4.8; XAMPP's older PHP should not run the application. Open http://127.0.0.1:8000. The local database is `tcgc_erdss_local`. MySQL must remain running. Apache is only needed if using phpMyAdmin at http://localhost/phpmyadmin.

For the existing sample workspace, the password is `DemoPass123!` and the accounts are `hr.admin@tcgc-demo.local`, `executive@tcgc-demo.local`, `dept.head@tcgc-demo.local`, and `employee@tcgc-demo.local`. These accounts are for rehearsal. Do not use them as client institutional accounts or present their sample responses as actual study records.

## 2 Prepare the real source material

Ask the client for the authorized employee roster, exact departments, positions, employee categories and employment types, the paper forms, and the actual collection dates or collection log. Confirm the approved analysis sample and how missing or multiple marks were handled. Keep original forms unchanged and under the client's control.

The paper reports 285 institutional employees, 190 survey respondents, and 15 interview participants. These are different counts. A current roster need not still contain exactly 285 employees. Load the roster that is actually applicable to the intended operating period.

Use `docs/templates/TCGC-Paper-Response-Entry.xlsx` to transcribe paper answers. It contains blank entry cells and a 56 item codebook; it contains no participant answers. Assign anonymous paper codes such as `PAPER-0001`. Do not put names, emails or employee numbers in that worksheet. Keep the employee roster in its separate import file. The question codebook corrects the two mistaken Appendix D section headings without changing the actual item text.

## 3 Create a separate institutional database

Before switching databases, stop the application server with Ctrl+C. Do not keep a second server running from the same project folder while editing its `.env`.

1. In phpMyAdmin select `tcgc_erdss_local`, choose Export, SQL, and save a backup in a private location. Keep the existing database.
2. Save a private backup of the current environment configuration:

   ```powershell
   Copy-Item .env storage/app/private/demo-environment-backup.txt
   ```

   This backup contains credentials and keys. It stays private and should not be included in a public project archive.

3. In phpMyAdmin create a new empty database named `tcgc_erdss_real`, with `utf8mb4_unicode_ci`. Verify that you selected the new database.
4. Open `.env`. Use `docs/institutional.env.example` as a reference. Set `DB_CONNECTION=mysql`, `DB_DATABASE=tcgc_erdss_real`, and the actual database host, port, username and password. Set `APP_ENV=local`, `APP_DEBUG=false`, `APP_URL=http://127.0.0.1:8001`, `SESSION_COOKIE=tcgc_erdss_real_session`, and clear `APP_KEY` for this independent environment.
5. Run:

   ```powershell
   php artisan config:clear
   php artisan key:generate
   php artisan migrate
   php artisan retention:initialize
   ```

   Do not add `--seed`: the ordinary seeder creates demonstration records. The initializer requests the real HR administrator's name and email, then a hidden password of at least 12 characters and confirmation. Use a private password selected for this installation. It refuses an already populated database.

6. Run:

   ```powershell
   php artisan serve --port=8001
   ```

   Open http://127.0.0.1:8001 and sign in with the administrator just created.

The initializer creates four role definitions, the retention outcome and eight predictors, reference feedback categories and themes, branding/settings, reviewable management thresholds, and one HR account. It creates zero employees, answers, periods, feedback submissions or interventions. Organization master lists and the recommendation library start empty. The new workspace uses operational data by default. Its Demo label is off because it contains no synthetic operational records; the published Research baseline remains separately available.

To return to rehearsal later, stop the real server, restore the saved demo `.env`, run `php artisan config:clear`, and start port 8000 again. Do not run database reset commands. Keeping a separate application folder and configuration is preferable if both environments must run simultaneously; have the installer prepare it without copying private sessions or credentials into a public package.

## 4 Configure the institution and roster

Sign in as the new HR administrator.

1. Open Organization setup. Add the institution's real Departments, Positions, Employee Categories and Employment Types. Names must match the import file. Use the roster's actual classifications, not the sample workspace's four departments.
2. Open Settings. Check institution and system names, logo and green/gold palette, report footer, minimum group size, session timeout and anonymous feedback policy. The default group size is five. Named feedback starts disabled in a clean installation.
3. Review Management Action Thresholds with HR. These are management rules, separate from the questionnaire's published score interpretation. New defaults are marked for review; the fact that they exist does not mean the institution has accepted them.
4. In Employees choose CSV Template. Its columns are:

   ```text
   employee_no,first_name,middle_name,last_name,email,department,position,category,employment_type,date_hired,employment_status
   ```

   Required: employee number, first and last name, department, position, category, employment type. Other fields may be blank. Use real employee numbers as text so leading zeroes survive, dates as `YYYY-MM-DD`, and `active` for active employees. Master names must match active entries, ignoring case.
5. Save as CSV UTF-8 and use Import CSV. The current limit is 5 MB and 10,000 rows. The importer inserts new employees; duplicate employee numbers and invalid rows are skipped. It does not update matching employees and does not create user accounts. Reconcile imported and skipped counts with the source, then inspect several records and their departments.
6. To record a genuine separation, use the employee's Separate action and enter its actual date and type. Importing only `employment_status=separated` does not create dated separation history. Do not invent a separation date to complete a chart.
7. In User management create the designated Executive, Department Head and Employee accounts. Link each employee or head account to its actual employee record. A head's department scope follows that record. Use institution controlled accounts and private passwords. The system protects the last active HR administrator.

## 5 Enter the historical paper questionnaire responses

This path imports actual past responses without impersonating employees or creating false online participation.

1. In Assessments choose New Assessment. Give it an explicit historical title, actual collection start/end dates and instructions. Keep status draft and scale minimum 1, maximum 4. Set labels to:

   ```text
   1=Strongly Disagree|2=Disagree|3=Agree|4=Strongly Agree
   ```

2. Save. Open Manage and note the period ID in its address, for example `view=1`. In PowerShell install the manuscript instrument, using the actual ID:

   ```powershell
   php artisan retention:install-questionnaire 1
   ```

   This installs 56 questions only in an empty draft four point period: eight retention questions and six for each HRM factor. It refuses an existing or used questionnaire. Check every factor and item in the codebook and browser before proceeding.
3. Edit the historical period and set status closed. Closed means these are past paper responses, not an invitation for new online responses.
4. In the entry workbook, use one row per actual paper form. Its first five columns are `respondent_code,submitted_date,department,category,employment_type`, followed by `Q001` through `Q056`. The Codebook maps every code to its exact factor and statement. Enter only integer answers 1, 2, 3 or 4.
5. Enter the real collection date as text `YYYY-MM-DD`, within the period's dates. Obtain it from the form or the researcher's collection log. Do not use today's transcription date in place of an unknown original date. The importer stores midnight only as a date representation; it is not a claim about the original time of day.
6. Use the original response classification for department, employee category and employment type, matching the active master lists. These are historical response snapshots; they need not be inferred from a person's current HR record. A code such as PAPER-0001 identifies a form, not an employee. Avoid storing an identity crosswalk with the response CSV.
7. Leave missing, unreadable or multiple marks blank for researcher review. Do not enter 0, choose a convenient answer, or copy a mean into an individual answer. The importer rejects incomplete rows. The researcher must approve a missing response policy before any exclusions or instrument changes are made.
8. Have a second person compare every entered value with its paper form, including whether 4 and 1 were reversed. Check duplicate paper codes and real totals. If reproducing the study, investigate discrepancies from 190 and the original analysis rather than forcing the data to match.
9. Save only the Responses worksheet as CSV UTF-8, for example `storage/app/private/paper-responses.csv`. Keep the XLSX as the transcription source. Run a dry run first:

   ```powershell
   php artisan retention:import-paper-responses storage/app/private/paper-responses.csv --period=1
   ```

   Review and correct all errors. The default dry run writes no responses. Then deliberately commit:

   ```powershell
   php artisan retention:import-paper-responses storage/app/private/paper-responses.csv --period=1 --commit
   ```

   The validated batch is atomic, records import provenance, and prevents duplicate paper codes in that period. It does not create employees or identity linked completion records. Do not rerun a committed batch as a way to update answers; corrections need a reviewed source correction process.
10. Open Retention overview, select Operational assessment data and this historical period, then Apply filters. Check respondent count and all nine means against an independent calculation. The published baseline is still a separate reference. Paper import does not refit the regression or establish individual online completion; participation percentage is not tracked for it.

If an original date, classification or mark cannot be established, stop that row for researcher clarification. The current importer is deliberately a complete response workflow; it is not an automated missing data analysis tool.

## 6 Collect a new operational assessment

For a new collection, create another draft period, install the same approved instrument, review it, then set it active with dates covering collection. Each employee signs in to their own account and submits once through My assessments. Do not enter multiple people's real answers through one employee account. The answer set has no employee ID; completion is recorded separately. Once responses exist, question additions and removals are locked.

When collection is complete, set the period closed. Keep the same wording, factor definitions and 1 to 4 scale for meaningful comparisons. The assessment editor supports other numeric bounds, but this capstone's analytics and interpretation assume four points. Do not use a different scale for this study.

Before new research collection, confirm consent, the handling of declined items and withdrawal with the researcher. The online form requires its required items and does not itself implement the manuscript's full research consent and withdrawal procedure. Keep the approved institutional process alongside the software.

## 7 Client demo sequence

Use real records read only where possible. Rehearse fictitious submissions and approvals in the sample database or a separate rehearsal copy, so the real dataset does not acquire demonstration answers or dummy interventions.

1. **Explain the data source.** Show Research baseline as the published study and Operational assessment data as the selected real period. Say: "285 is the workforce reference, 190 is the survey sample, and 15 is the interview sample. This view identifies which source is being shown."
2. **Show the overview.** Point out retention mean, workforce records, respondent count, HRM scores and unit filters. Explain small group suppression rather than treating a blank score as an error.
3. **Show Predictive insights.** Explain the published beta ranking and 39.9 percent variance explained. Show that four Pearson correlations and eight simultaneous regression coefficients are significant. Say: "The application presents these research findings; it does not automatically retrain a prediction model or identify who will resign."
4. **Show employee experience and feedback.** Distinguish the seven published interview themes from newly tagged comments. In Feedback review demonstrate General and an assessment period. HR assigns themes manually. Avoid exposing identifiable real feedback in a public demonstration.
5. **Show a management action.** Review a real factor in Priorities & actions, then its recommendation. Explain how HR creates an intervention and the Executive approves it. Use a rehearsal record if no authorized real intervention exists.
6. **Switch roles.** Show the assigned Department Head's limited scope and progress update form. Show Employee My assessments, Organizational results, HR actions and Privacy. Do not change a person's actual answers for demonstration.
7. **Show monitoring and reporting.** Compare two genuine comparable periods if available. If there is only one, explain why before/after results are unavailable instead of fabricating a second period. Export a report and open the policy report's Print / save PDF.
8. **Close with administration.** Show employee import, Organization setup, accounts, settings and audit activity. Confirm client responsibilities and the reconciliation/acceptance checklist below.

## 8 Every menu feature explained

### Retention overview

HR, Executive and Department Head see summary retention, workforce distribution and HRM factor information. Operational mode supports period/unit selection and recorded history. Department Heads are restricted to their linked department. Employee accounts see their own workspace with assessment and feedback links. The research workforce and published model remain reference context.

### HRM analytics

HR, Executive and Department Head inspect factor and outcome means by selected period and permitted department. Retention is a separate outcome; the overall HRM score excludes it. Explain the four point interpretation and whether a value is suppressed because too few respondents answered that factor.

### Predictive insights

HR and Executive see the manuscript's standardized beta ranking, significance and explanatory power, with the Pearson comparison. Performance Management has the largest published beta .344, followed by Leadership .266 and Engagement .257. These numbers describe the original full regression, not a new model trained on today's database.

### Employee experience

HR and Executive see the seven research themes and separately aggregated operational feedback information. Theme coding is done by HR. It is not automatic sentiment analysis and does not turn the original 15 interviews into employee records.

### Priorities and actions

HR and Executive review current and previous factor scores, trends, suggested actions and recommendation links. HR saves pending, confirmed or dismissed review states. Management thresholds and a stability band drive the suggestions. These are not individual risk probabilities or the manuscript's validated interpretation bands. HR must approve their operational use.

### Monitoring and evaluation

HR and Executive compare two assessment periods. Scores require the privacy minimum. HR can save an evaluation for an approved intervention with a narrative note. A difference is temporal evidence, not proof that an intervention caused it. Formal review dates, minutes and effectiveness decisions remain management tasks.

### Reports

HR, Executive and Department Head can view permitted reports, export CSV and print. The six types are Retention Summary, HRM Factors, Feedback Themes, Priority Areas, Intervention Progress, and Pre/Post Comparison. Department Heads cannot receive institution wide Priority Areas and other report data remains scoped. Intervention Progress describes the register rather than a response-period dataset. Policy review report produces an executive summary for browser PDF printing and keeps published model context labeled.

### Employees

HR can add/edit employees, search and filter the roster, download the CSV template, import, archive and record separations. Archiving is not a substitute for recording a separation date. Importing employees does not create accounts or questionnaires.

### Assessments

HR creates draft, active and closed periods, sets dates/instructions and manages factor linked questions. The approved installer supplies all 56 items to a new draft. Question/response totals are visible in the list. New online submission requires an active period within dates. Used questionnaire structure is locked. Historical paper imports use a closed period.

### Feedback review

HR selects an assessment or General / not tied to a period, reads comments, adds themes and tags comments. Named submissions identify the employee only if the policy allows them. Anonymous comments can still contain identifying details in their text, and HR sees full comments; do not promise absolute anonymity.

### Interventions

HR creates an action with its factor, objectives, owner/unit, budget, dates and expected outcome, then submits it for approval. An Executive approves or rejects pending items. Editing an intervention resubmits it for approval. HR or the assigned Department Head adds status/progress notes once approved. The register shows the current status; there is no full progress-note history panel.

### Recommendations

Management can read a factor linked action library. HR creates or maintains entries. New institutional setup leaves this library empty so HR can enter approved real recommendations rather than inherit synthetic projects. Recommendations inform interventions; they do not automatically authorize spending or implementation.

### Organization setup

HR maintains Departments, Positions, Employee Categories and Employment Types. These supply the employee and paper-response classifications. Set accurate names and statuses before importing data. The manuscript's employment category is distinct from employment type and from active/separated workforce status.

### HRM factors

HR maintains factor names, descriptions, display order and status. The initializer supplies the eight approved factors and the separate Employee Retention outcome. Keep these definitions stable for the approved instrument. Adding another factor does not update the published regression model.

### User management

HR creates and edits accounts, assigns one role, sets active/inactive status and passwords, and links employee/head accounts. Department scope comes from the linked employee. The last active HR administrator is protected. There is no self service password reset or employee password change screen; HR manages that process.

### Audit log

HR reviews the latest 200 recorded actions with time, account, action and summary. Use it to support change reviews. It is not a replacement for a database backup or a complete institutional acceptance record.

### Settings

HR updates labels, logo, colors, footer, privacy minimum, timeout, named feedback policy, frequency and management thresholds. Frequency is descriptive; it does not schedule emails or surveys. Hiding Demo changes labels; it never removes sample records. The clean initializer avoids sample data rather than hiding it.

### My assessments

Employees see available and closed periods and their recorded online completion status, and submit an active assessment once. Historical paper answers are not linked to employee completion and must be described separately. Required items must be answered for online submission.

### Share feedback

Employees select a category, optional period and comment. Anonymous submission is available; institutional policy can disable named feedback. A General comment can now be reviewed by HR without tying it to an assessment.

### Organizational results

Employees see institution wide aggregate retention and factor results from the applicable operational period, subject to privacy suppression. They do not receive another employee's answer set.

### HR actions

Employees see approved, non-cancelled interventions explicitly marked public. Internal or pending interventions stay outside this employee view. Show a genuine approved public action or a clearly identified rehearsal record.

### My profile

All roles can review their account name, email and role. This page is read only. Account corrections and password resets go through HR User management.

### Privacy

Employees can read the explanation of separate identity/completion tracking, aggregate results and feedback handling. It explains that the system does not produce individual resignation risk scores. Actual access permissions, consent and institutional procedures must still be followed in operation.

## 9 Reconcile and accept the real data installation

- Match employee import totals and classification counts to the authorized roster; resolve skipped rows.
- Match the number of imported paper forms to the reviewed source forms, not automatically to 190 if the available forms differ.
- Independently calculate each respondent's eight-item retention average, each six-item factor average, then group means. With complete responses this agrees with the system's item averages. Compare at the paper's published precision and investigate discrepancies.
- Check 56 questions, correct factors, scale 1 to 4, and actual period dates. Do not silently revise historical questions after import.
- Verify the selected source/period on every client-facing report. Published coefficients and the original research baseline do not change when operational responses change.
- Check HR, Executive, assigned Head and Employee access, including direct links outside the role. Check suppression below the approved minimum.
- Export each applicable CSV and the policy PDF. Open files and confirm scope, dates, respondent totals and score interpretation.
- Export a private MySQL backup through phpMyAdmin and restore it into a new verification database. Compare counts before handover. Include uploaded files and private environment configuration in the institution's controlled backup process.
- Record client acceptance: reviewer, date, data source/version, checks performed, unresolved items, approved thresholds and agreed operating responsibilities.

The application does not schedule backups, send automatic reports, retrain regression or automatically classify themes. For institution-wide access beyond this computer, have the institution's IT staff configure the host, access and backup arrangements. `127.0.0.1` reaches only the computer where the server runs.

## 10 Common problems

| Symptom | Check |
| --- | --- |
| Cannot connect to MySQL | Start XAMPP MySQL and verify host/port in `.env` |
| Wrong records or missing users table | Confirm the database selected in phpMyAdmin matches `.env`; clear config cache after editing |
| Initializer refuses | It is preserving an already populated database; select a genuinely empty institutional database and migrate without seeding |
| Employee rows skipped | Check duplicate employee numbers, active master names, date format, email and column counts |
| Paper import rejected | Read the row/question errors; verify real dates, 56 answers, classifications, unique paper code and closed historical period |
| Questionnaire installer refuses | Use an unused draft period with 1 to 4 scale and all approved active measures |
| No score or comparison | Check response counts, per-factor privacy minimum, selected period and whether two comparable periods exist |
| Demo labels remain | Current data is still marked sample; initialize a clean real database rather than hiding sample provenance |
| Server error | Inspect `storage/logs/laravel.log`; keep real data debugging private |

For local regression checks, this computer can run the isolated test suite with `php -d extension=pdo_sqlite vendor/phpunit/phpunit/phpunit`. This enables the test driver only for that command; the application remains configured for MySQL.
