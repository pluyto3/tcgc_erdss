# Manuscript alignment and handover review

Reviewed on October 20 2026 against Junila O Catipay's final manuscript, September 2026, titled *Predictive Analytics for Employee Retention A Data Driven Decision Support Dashboard for Human Resource Management at Tangub City Global College*. References below use chapter, table and appendix labels from the supplied manuscript, rather than unverified page numbers. The original manuscript was not edited.

## Conclusion

The system substantially aligns with the organizational decision support dashboard described in Chapter 5. All 56 questionnaire item texts match Appendix D exactly. The published retention score, eight HRM factor means, regression coefficients and significance values, and model R squared agree with Chapter 4.

This does not establish that real operational data has already been loaded, that a new regression model has been fitted, or that client acceptance is complete. The current `tcgc_erdss_local` database remains a demonstration database. The client has an employee list and paper questionnaire responses. Those paper forms need transcription, validation and reconciliation before the original study can be represented as imported records.

## Three populations that must stay distinct

| Figure | Meaning | Manuscript evidence |
| --- | --- | --- |
| 285 | Institutional workforce across five units | Chapter 1 Table 1 |
| 190 | Quantitative survey respondents | Abstract and Chapter 4 Tables 3 through 9 |
| 15 | Qualitative interview participants | Methods and qualitative results |

The baseline dashboard now states these separately. Its workforce card refers to the institutional workforce, not the survey sample. A new institutional database defaults to operational assessment data. Research baseline remains available as explicitly labeled published context.

## Published results checked

The retention mean is **2.821**, normally displayed as **2.82 out of 4**, with a High interpretation. The simultaneous model reports R = .632, R squared = .399, adjusted R squared = .336, and p < .001. The model contains demographic variables as well as the eight HRM factors. Its 39.9 percent is variance explained within the study sample, not prediction accuracy and not a probability of resignation.

| HRM factor | Mean | Standardized beta | Regression p | Pearson r | Correlation p |
| --- | ---: | ---: | --- | ---: | --- |
| Compensation and Benefits | 2.654 | .219 | < .001 | .119 | .101 |
| Career Development | 2.834 | .138 | .026 | .137 | .059 |
| Training and Development | 2.739 | .161 | .011 | .134 | .065 |
| Performance Management | 2.725 | .344 | < .001 | .313 | < .001 |
| Work Environment | 2.777 | .191 | .003 | .114 | .117 |
| Leadership and Management | 2.709 | .266 | < .001 | .246 | .001 |
| Employee Engagement | 2.937 | .257 | < .001 | .286 | < .001 |
| Job Satisfaction | 2.921 | .150 | .016 | .181 | .013 |

Source: Chapter 4 Tables 5, 6 and 9. Four correlations are significant at alpha .05: Performance Management, Leadership and Management, Employee Engagement, and Job Satisfaction. All eight HRM coefficients are significant in the simultaneous regression. These are different analyses. Predictive insights now presents the distinction explicitly and identifies the final manuscript as its source.

The seven configured experience themes match Table 10: compensation and security; growth; recognition and voice; supportive leadership and relationships; healthy work environment; workload; communication and administrative support. Published theme frequencies are overlapping participant mentions, not mutually exclusive proportions of the 190 respondents. New feedback tags are counts of reviewed comments, not a reproduction of the original interview analysis.

## Questionnaire and dashboard traceability

| Manuscript requirement | System implementation | Assessment |
| --- | --- | --- |
| Separate retention outcome and eight HRM predictors | Nine measures, with Employee Retention flagged as the outcome | Aligned |
| Appendix D instrument | Eight retention items plus six per HRM factor, 56 total, matched item by item | Exact text match |
| Four point response scale | 1 Strongly Disagree, 2 Disagree, 3 Agree, 4 Strongly Agree | Use 1 to 4 for this capstone |
| Mean interpretation | 1.00 to 1.75 Very Low, above 1.75 to 2.50 Low, above 2.50 to 3.25 High, above 3.25 Very High | Aligned with the published bands |
| Executive overview | Score, workforce, unit distribution, operational response counts and history | Aligned; workforce and sample are distinguished |
| HRM factor monitoring | Factor means, interpretation, unit and assessment filters | Aligned |
| Predictive insights | Published beta values, significance, R squared, Pearson comparison and interpretation | Aligned as research reference; no automatic model fitting |
| Employee experience | Seven research themes plus separately reviewed operational feedback | Aligned; coding is manual |
| Priority and action panel | Reviewed suggestions, recommendations and intervention workflow | Aligned; the three tier presentation and management thresholds are implementation choices |
| Monitoring and reporting | Before and after comparison, evaluation records, six CSV reports and policy PDF printing | Aligned; a change does not establish causation |
| Authorized access | HR, Executive, Department Head and Employee permissions; unit scope; small group suppression | Implemented; real accounts and authorized assignments still need client verification |

## Finalization changes made during this review

- Added a clean institutional initializer which creates reference definitions and one private HR account, with no invented employees or responses. It refuses a database that already contains records.
- Extracted one shared approved questionnaire bank and added an installer for unused draft periods.
- Added review of General feedback that is not linked to an assessment period.
- Locked questionnaire additions and removals once answers have been collected.
- Corrected study provenance and clarified 285 workforce versus 190 survey and 15 interview participants.
- Added a dry run and validated import path for transcribed anonymous paper forms, with duplicate protection and recorded provenance. Actual forms have not been entered.

## Matters for the manuscript author and adviser

These findings concern the manuscript. They should be reconciled with the original research records, not fixed by altering real answers to fit a desired result.

1. **Eligibility:** the methods exclude employees with less than one year of service, but Table 3 and Appendix E include 17 such respondents. Confirm the actual approved eligibility criteria and analysis sample.
2. **Population wording:** the institutional workforce is 285, while the sampling discussion also calls 190 the target population. Distinguish workforce, eligible sampling frame, selected sample and achieved responses.
3. **ANOVA explanation:** the text says regression sum of squares 1.837 exceeds residual sum of squares 2.767. It is smaller. Check the prose against Table 8 and the original output.
4. **Standard deviations:** the narrative gives Performance Management .2610 and Leadership and Management .2611; Table 5 gives .271 and .300. Reconcile with the original statistical output.
5. **Appendix D headings:** the six Performance Management questions appear below a repeated Training and Development heading. The six Employee Engagement questions appear below a repeated Leadership and Management heading. The application maps these items to their correct factors.
6. **Terminology:** the methods mention nine HRM factors, although there are eight predictors and one separate retention outcome. The abstract says seven themes but appears to omit workload as a separate theme. The sequential design also has inconsistent simultaneous wording.
7. **Predictive claims:** the supplied tables omit the intercept and do not report independent test set performance. They support presentation of the published associations, not an independently validated resignation forecasting service. Chapter 5 expressly excludes automatic individual classification.

## Operating limits to explain during the demo

The system does not reproduce the whole original statistical analysis. It does not currently fit a fresh demographic regression, calculate all reliability diagnostics, or collect age and sex as survey snapshots. It does not establish causal effects of interventions. Do not present the published beta values as recalculated from the imported forms.

Employee Retention measures intention or willingness to remain. The workforce active and separated shares are record status proportions, not conventional annual turnover or cohort retention rates. Online participation uses recorded completions and the current active employee roster, not a frozen historical sampling frame. Imported anonymous paper records must not be linked to fabricated employee completion records; their participation percentage is not tracked.

Appendix D permits declining questions and withdrawal. The current online form requires answers to its required items and does not implement a separate consent or anonymous answer withdrawal process. Before collecting a new research sample, the researcher must settle the approved consent and missing response procedure. In the transcription workflow, leave missing or ambiguous marks blank for review; the current complete case importer stops rather than imputing them. This is not a decision to exclude those participants from the original study.

HR can reset account passwords through User management; there is no employee self service password change or recovery flow. Intervention progress notes are stored, but the register primarily shows current status. Monitoring schedules, management minutes, data quality reviews and usability acceptance remain human processes even where the application supplies supporting records.

## Conditions for real data handover

Use the companion client guide to initialize an independent real database, enter approved master data, transcribe and double check the paper forms, validate the import without writes, and reconcile actual respondent totals and means. Investigate any discrepancy from the published paper. Check scope restrictions and exports with designated client accounts, test a backup restoration into a separate database, and record client acceptance.

The system is ready for this controlled onboarding process. A complete real data certification requires the actual roster, paper transcription, source validation, and client acceptance; it cannot be concluded from the manuscript alone.
