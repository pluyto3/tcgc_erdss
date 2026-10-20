# TCGC system: step-by-step user guide

Use this guide while the application is open in your browser. Menu, button and field names below match the current application. These instructions do not add records automatically.

Start with the existing demonstration workspace for practice. Enter authorized institutional records in the separate real-data database described in [Client handover guide](CLIENT-HANDOVER-GUIDE.md). Hiding the Demo label does not remove sample records.

## 1. Open the system and choose the right account

1. Start **MySQL** in the XAMPP Control Panel.
2. If the application server is not already running, open PowerShell and run:

   ```powershell
   cd C:\xampp\htdocs\tcgc_erdss
   php artisan serve
   ```

3. Open **http://127.0.0.1:8000**. If your separate real-data installation was started on port 8001, use **http://127.0.0.1:8001** instead.
4. Sign in as **HR Administrator** for setup and data entry. In the existing demonstration workspace only, use `hr.admin@tcgc-demo.local` and `DemoPass123!`.
5. Check the role below your name at the bottom of the sidebar. Scroll the sidebar to reach **Administration**. On a narrow screen, open the navigation menu first.

| Role | Main responsibility |
| --- | --- |
| HR Administrator | Organization lists, employees, accounts, assessments, feedback review and action planning |
| Executive | Review institution-wide information and approve or reject interventions |
| Department Head | View assigned-unit results and update approved interventions for that unit |
| Employee | Submit their own assessment and feedback; view published aggregate results and HR actions |

If Organization setup or User management is missing, check that you signed in as HR Administrator. Menus depend on the account's role.

## 2. Add a department

1. In the sidebar, click **Organization setup** under **Administration**.
2. Click the **Departments** tab.
3. Check the existing list to avoid creating the same department twice.
4. Find the **Add Departments** form beside or below the list.
5. Enter the institution's approved **Name**, for example `Academic Affairs Unit` if that unit does not already exist.
6. Enter an optional **Code**, for example `AAU`. Names must be unique; a supplied code must also be unique.
7. Leave **Status** set to `active`.
8. Click **Save**.
9. Confirm that the department appears in the list with an active status. It will be available in the **Department** list when you add an employee.

To correct a name or code, click **Edit** on its row, change the value and click **Save**. To stop offering a department for new assignments, edit its status to `inactive`. This preserves the record; it does not transfer its employees or deactivate their user accounts. There is no department Delete button. Return to the Departments tab before adding another record if the form still says Edit.

Use the same official unit names in your employee CSV and paper-response CSV. For this study, confirm with the client whether these entries should be the questionnaire's organizational units.

## 3. Add positions, employee categories and employment types

Repeat the following process for each tab in **Organization setup**:

1. Click **Positions**, **Employee Categories** or **Employment Types**.
2. Check the list for an existing entry.
3. Enter **Name** in the Add form.
4. Set **Status** to `active`.
5. Click **Save** and check the new row.

Only departments have a Code field. Use Edit to correct or deactivate an existing entry.

| List | Meaning | Example, if approved by the institution |
| --- | --- | --- |
| Departments | Organizational unit | Academic Affairs Unit |
| Positions | Employee's job title | Instructor |
| Employee Categories | Broad personnel classification | Faculty Personnel; Administrative Personnel |
| Employment Types | Appointment or contract classification | Permanent/Regular; Contractual |

Keep employment type separate from the employee's workforce status, which is **Active** or **Separated**. Add the four organization lists before adding employees.

## 4. Add one employee

1. Click **Employees** under Management.
2. Click **Add Employee**.
3. Enter **Employee No**, **First Name** and **Last Name**. The employee number must be unique. Preserve leading zeroes.
4. Enter **Middle Name**, **Email** and **Date Hired** when available. These fields may be blank; do not invent missing details.
5. Select **Department**, **Position**, **Category** and **Employment Type**. All four selections are required. If a choice is missing, add it as an active entry in Organization setup first.
6. Leave **Status** as **Active** for a current employee.
7. Click **Save**.
8. Search for the employee or select their department, then click **Filter**. Check the saved details.

To change an employee's assignment or correct details, click **Edit** on their row, update the fields and click **Save**. Adding an employee creates an HR record. Create a separate user account in the next step if they need to sign in.

For many employees: click **CSV Template**, fill the downloaded file without changing its headers, save as CSV UTF-8, choose the file under **Import Historical Employee Records**, and click **Import CSV**. Organization names must match active entries. Review the imported and skipped totals. This importer inserts new employees; duplicate employee numbers are skipped rather than updated. Full column instructions are in the handover guide.

For a genuine separation: find the employee, click **Separate**, enter **Separation Date**, select **Type**, add **Notes** if appropriate, and click **Save Separation**. Types include Resignation, Retirement, End of Contract and Other. Use this action to create dated separation history. Merely editing Status to Separated does not create that history.

**Archive** removes a record from the normal roster list while retaining it in the database. The current interface has no restore button. Use it deliberately; use Separate for an actual resignation or retirement.

## 5. Create an account and assign its role

1. Click **User management** under Administration.
2. In **Create User**, enter **Name** and **Email**. The email must be unique.
3. Select **Role**: HR Administrator, Executive, Department Head or Employee.
4. For an Employee or Department Head, choose that person's record under **Employee Link**. These roles require the link. A Department Head's unit access follows the linked employee's department.
5. Set **Status** to **Active**.
6. Enter a private **Temporary Password**. The form accepts at least eight characters; use a strong private password, preferably at least twelve characters.
7. Click **Create User** and confirm the account appears in the list.
8. Use **Sign out** beside your account in the sidebar, then sign in with the new account to verify its menus and permitted scope. Use your own rehearsal accounts for demonstrations.

To update an account, click **Edit**, change the details and click **Save User**. Leave **New Password (leave blank to keep current)** empty to retain the password. Enter a new password there when HR needs to reset it. The current My profile screen is read only; there is no employee self-service password change screen.

Set an account to **Inactive** to disable sign-in. Archiving or separating an employee is a separate action; review their account status too. Keep at least one active HR Administrator. Each employee record can be linked to only one account.

## 6. Create an assessment with the approved questionnaire

1. Click **Assessments**, then **New Assessment**.
2. Enter **Title** and **Description / Instructions**. Use a title that identifies the real collection period.
3. Enter **Start Date** and **End Date**.
4. Keep **Status** as `draft` while preparing questions.
5. Set **Scale Minimum** to `1`, **Scale Maximum** to `4`, and keep **Scale Labels** as:

   ```text
   1=Strongly Disagree|2=Disagree|3=Agree|4=Strongly Agree
   ```

6. Click **Save Assessment**.
7. Click **Manage** on the new assessment. The question list will initially be empty.
8. To install all 56 approved questions, note the period number in the Manage address, such as `view=3`, and run the following from the project folder in PowerShell, replacing `3` with that actual number:

   ```powershell
   php artisan retention:install-questionnaire 3
   ```

9. Refresh Manage and verify **56 questions**: eight Employee Retention items and six items for each of the eight HRM factors. The installer requires an unused draft with the correct four-point scale.
10. For a **new online collection**, click **Edit Assessment**, change Status to `active`, check the dates cover collection, then click **Save Assessment**. Close it when collection ends.
11. For **existing paper forms**, change Status to `closed` instead and follow step 7 below. A historical paper period stays closed.

The Manage page also has **Add Question**, with Factor, Question and Display Order, plus Remove on unused questions. Use the approved installer for this capstone to preserve the exact instrument. Once any responses exist, question additions and removals are locked. Create a new period for a changed instrument.

## 7. Enter the existing paper forms

Paper responses and the employee roster are separate sources. Do not enter every person's answers through one employee account.

1. Open [TCGC-Paper-Response-Entry.xlsx](templates/TCGC-Paper-Response-Entry.xlsx).
2. Read Instructions and use the Codebook to identify Q001 through Q056.
3. On Responses, use one row per actual completed form. Assign a code such as `PAPER-0001`; enter the actual collection date, department, category and employment type.
4. Enter the 56 marked ratings as integers from 1 to 4. Leave unclear or missing marks for researcher review; the current importer rejects incomplete rows.
5. Have another person check each row against its original form. Do not put names, emails or employee numbers in this worksheet.
6. Save only Responses as CSV UTF-8 to a private location, such as `storage/app/private/paper-responses.csv`.
7. With the correct closed period ID, run a validation only:

   ```powershell
   php artisan retention:import-paper-responses storage/app/private/paper-responses.csv --period=3
   ```

8. Correct any reported errors. Only after reviewing the validated batch, import it:

   ```powershell
   php artisan retention:import-paper-responses storage/app/private/paper-responses.csv --period=3 --commit
   ```

9. Confirm the response count and independently check the calculated means. Duplicate paper codes are blocked. There is currently no browser upload button for this paper-response CSV.

Use actual original collection dates within the historical period; consult the collection log when needed. The importer currently captures the 56 ratings plus organizational unit, employee category and employment type. It does **not** capture age, sex, length of service or the two optional written answers. Retain those original fields separately for researcher review. Paper imports do not establish individual online completion or a participation percentage.

See handover guide section 5 for complete import requirements. Do not import real forms into the sample workspace.

## 8. Submit a new online assessment as an employee

1. Sign in with the employee's own linked account.
2. Click **My assessments**.
3. Find an active assessment and click **Take assessment**.
4. Read the instructions and select one rating for each required question.
5. Click **Submit Assessment**, review the confirmation and confirm submission.
6. Check that Your Status becomes **Completed**.

Each employee can submit once per period. They cannot edit answers after submission. HR should confirm the approved consent and missing-response procedure before inviting new research responses. Historical paper periods display **Paper form import; completion not linked**.

## 9. Read dashboard results and HRM analytics

1. Sign in as HR Administrator, Executive or Department Head.
2. Open **Retention overview**.
3. For HR or Executive, select **Data source**. Research baseline shows the published study. Operational assessment data shows the selected database period; the same option is called Demo assessment data in the sample workspace.
4. Click **Apply filters** to switch data source. In operational mode, select **Assessment period** and **Operational unit**, then apply again if needed.
5. Read the respondent count, retention mean and HRM factor results for that source and scope. Department Heads have their own unit scope.
6. Open **HRM analytics**, select the period and permitted department, and click **Apply Filters**. Review Employee Retention, Overall HRM Mean and Factor Details.

Scores run from 1 to 4. Interpretation bands are 1.00-1.75 Very Low, 1.76-2.50 Low, 2.51-3.25 High and 3.26-4.00 Very High. Groups below the configured privacy minimum, normally five, have results suppressed. A suppressed or unavailable score is not zero. The Overall HRM Mean excludes the separate retention outcome.

**Predictive insights** is available to HR and Executive: open it to review the manuscript's published predictor ranking, regression results and Pearson comparison. These are research reference results, not an automatically refitted model or an individual resignation probability. The reported 39.9% is variance explained in the original study, not prediction accuracy.

## 10. Submit feedback and review themes

As an Employee:

1. Click **Share feedback**.
2. Select **Assessment Period**, or leave **General / not tied to a period**.
3. Select **Category** and enter **Your Feedback**.
4. Choose **Submission Type**. Anonymous is available; Named feedback is accepted only where policy allows it.
5. Click **Submit Feedback**.

As HR Administrator:

1. Click **Feedback review**.
2. Select the assessment or **General / not tied to a period**, then click **Filter**.
3. Read each comment. In its row, choose a theme and click **Tag**. Multiple relevant themes can be added.
4. If an appropriate theme is absent, enter **Theme Name** in **Add Theme**, click **Add Theme**, then tag the comment.
5. Review **Top Feedback Themes**.

HR and Executive can open **Employee experience** to see the seven published research themes and separately filtered current feedback/perception aggregates. Theme tagging is manual. Anonymous feedback omits the employee identity link, but the written text itself may reveal identity. Paper open-ended answers are not imported automatically by the paper-response command.

## 11. Add recommendations and review priorities

To maintain the action library as HR:

1. Click **Recommendations**.
2. In **Add Recommendation**, enter **Title** and **Description**.
3. Select the relevant **HRM Factor**, set Status to **Active**, and click **Save Recommendation**.
4. Use **Edit** on an existing recommendation to revise it; save again.

To review suggested priorities:

1. Open **Priorities & actions** as HR or Executive.
2. Select **Assessment** and click **Apply filters**.
3. Compare Current, Previous, Trend, Action and Recommendation. Read the research tiers as reference context.
4. As HR, select Review status `pending`, `confirmed` or `dismissed`, then click **Save** on the relevant factor row.
5. Click **View interventions** when ready to plan an action.

Confirming a priority records HR's review; it does not create an approved intervention. Assessment suggestions require enough responses and use the management thresholds from Settings.

## 12. Create, approve and update an intervention

As HR Administrator:

1. Open **Interventions** and find **Create Intervention**.
2. Enter **Title**, select **HRM Factor**, and select **Priority Area** and **Recommendation** when applicable. Leave them as None for an approved manually initiated plan.
3. Enter the proposed **Budget (PHP)**, **Responsible Department**, **Responsible User**, **Start Date** and **Target Date**.
4. Describe **Issue**, **Description**, **Objective**, **Expected Outcome** and any **Internal Notes**.
5. Set **Employee Visibility after Approval** to **Internal only** or **Publish as HR Action**.
6. Click **Create & Submit for Approval**. Check that Approval is Pending in the register.

As Executive:

1. Sign in and open **Interventions**.
2. Review the pending proposal and budget.
3. Enter **Approval notes** if needed, then click **Approve** or **Reject**.

As HR or the Department Head for the responsible unit:

1. Open **Interventions** after approval.
2. Find the approved item and select its implementation status, such as In Progress or Completed.
3. Enter a **Progress note** and click **Add Update**.
4. Verify the current status in the register. Full progress-note history is not displayed in a separate history panel.

HR can click Edit to revise the plan, then **Save & Resubmit**. Editing requires Executive approval again. Approved, non-cancelled interventions marked Publish as HR Action appear in employees' **HR actions**.

## 13. Compare periods and save an evaluation

1. Open **Monitoring & evaluation** as HR or Executive.
2. Select **Before** and **After**, using two different comparable assessment periods.
3. Click **Compare** and review scores, differences and trends.
4. As HR, find **Save Intervention Evaluation**, choose **Approved Intervention**, **Before Period** and **After Period**, enter **Evaluator Note**, and click **Save Evaluation**.
5. Check the entry under **Saved Evaluations**.

At least two periods and enough responses in both are needed. With only one real period, comparison is unavailable. A later score improvement does not by itself prove an intervention caused it.

## 14. Export and print reports

1. Open **Reports** as HR, Executive or Department Head.
2. Choose Retention Summary, HRM Factors, Feedback Themes, Priority Areas, Intervention Progress or Pre/Post Comparison. Department Heads have a restricted set and unit scope.
3. Select **Assessment period** and click **Apply filters**. For Pre/Post Comparison also select **Before period** and **After period**.
4. Check the period, scope and values before sharing.
5. Click **Export CSV** for a spreadsheet file, or **Print report** for browser printing.
6. For the executive summary, click **Policy review report**, review it, then **Print / save PDF** and choose the browser's Save as PDF destination.

Exports retain the role's access restrictions and suppression rules. Intervention Progress describes the intervention register; selecting an assessment does not turn that register into period-specific questionnaire responses.

## 15. Update institution settings and review the research factors

1. As HR, open **Settings**.
2. Under Institution, Branding & System, review **Institution Name**, **System Name**, **Dashboard Title**, **Primary Color**, **Accent Color** and **Report Footer**.
3. To replace the included logo, choose a PNG, JPEG or WEBP file up to 2 MB under **Official Logo**.
4. Review **Minimum Group Size**, **Session Timeout (minutes)**, **Allow Named Feedback**, **Assessment Frequency** and **Default Scale** with the client.
5. Click **Save Settings**.
6. In the separate Management Action Thresholds form, enter institution-approved **Lower Threshold**, **Upper Threshold** and **Stability Band**, then click **Save Decision Rules**.

Frequency is descriptive; it does not automatically create a survey or send reminders. Default Scale does not rewrite existing periods. Management action thresholds are separate from the published score interpretation bands. Leave the Demo/Synthetic Data Banner visible while sample records are in use.

Open **HRM factors** to review the eight approved HRM factors and separate Employee Retention outcome. Its Add/Edit form has Name, Description, Display Order, Status and **Save**. Keep the approved factors stable for this capstone. Adding a factor does not alter the manuscript's regression model. The retention outcome cannot be deactivated.

## 16. Remaining menus and a suggested rehearsal

| Menu | How to use it |
| --- | --- |
| Audit log | HR opens it to review the latest 200 recorded actions, times, users and summaries. It is an activity record, not a backup. |
| Organizational results | Employee opens it to read institution-wide operational aggregates when enough responses exist. |
| HR actions | Employee opens it to read approved, public, non-cancelled interventions. |
| My profile | Any role opens it to check name, email and role. Ask HR to make account changes in User management. |
| Privacy | Employee opens it to read how answers, completion tracking, aggregates and feedback are handled. |

For a practice walkthrough in the sample database, demonstrate: Organization setup -> employee record -> linked account -> assessment -> results -> feedback tagging -> recommendation -> intervention -> Executive approval -> progress update -> report. Use clearly identified practice records. For the client's real historical data, follow the separate paper import path and reconcile its totals before presenting results as real institutional findings.
