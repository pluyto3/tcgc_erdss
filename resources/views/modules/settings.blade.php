<?php

$rule=decision_thresholds($db);
echo '<div class="notice"><strong>Client-approved system configuration.</strong> These values remain editable by HR Administrator so TCGC can update policy and branding settings without changing source code.</div>';

echo '<div class="form-card" style="margin-top:14px"><h2>Institution, Branding & System</h2><form method="post" enctype="multipart/form-data">'.csrf_field().'<input type="hidden" name="action" value="settings"><div class="form-grid">';
echo '<div class="form-group"><label>Institution Name</label><input name="institution_name" value="'.e(setting($db,'institution_name','Tangub City Global College')).'"></div>';
echo '<div class="form-group"><label>System Name</label><input name="system_name" value="'.e(setting($db,'system_name','TCGC Employee Retention Decision Support System')).'"></div>';
echo '<div class="form-group full"><label>Dashboard Title</label><input name="dashboard_title" value="'.e(setting($db,'dashboard_title','HRM Analytics & Decision Support Dashboard')).'"></div>';
echo '<div class="form-group"><label>Primary Color</label><input type="color" name="primary_color" value="'.e(setting($db,'primary_color','#173a5e')).'"></div>';
echo '<div class="form-group"><label>Accent Color</label><input type="color" name="accent_color" value="'.e(setting($db,'accent_color','#2f6f98')).'"></div>';
echo '<div class="form-group full"><label>Official Logo</label><input type="file" name="logo" accept="image/png,image/jpeg,image/webp"><span class="help">PNG, JPEG, or WEBP up to 2 MB. Current: '.e(setting($db,'logo_path','Not uploaded')).'</span></div>';
echo '<div class="form-group full"><label>Report Footer</label><input name="report_footer" value="'.e(setting($db,'report_footer','TCGC Employee Retention Decision Support System')).'"></div>';
echo '<div class="form-group"><label>Assessment Frequency</label><select name="assessment_frequency">';foreach(['Quarterly','Semestral','Annual','Custom'] as $x) echo '<option'.selected(setting($db,'assessment_frequency','Semestral'),$x).'>'.e($x).'</option>';echo '</select></div>';
echo '<div class="form-group"><label>Default Scale</label><input name="default_scale" value="'.e(setting($db,'default_scale','4-point Likert')).'"></div>';
echo '<div class="form-group"><label>Minimum Group Size</label><input type="number" min="2" max="100" name="minimum_group_size" value="'.e(setting($db,'minimum_group_size',5)).'"><span class="help">Aggregate results below this respondent count are suppressed.</span></div>';
echo '<div class="form-group"><label>Session Timeout (minutes)</label><input type="number" min="5" max="720" name="session_timeout" value="'.e(setting($db,'session_timeout',60)).'"></div>';
echo '<div class="form-group"><label>Allow Named Feedback</label><select name="allow_named_feedback"><option value="1"'.selected(setting($db,'allow_named_feedback'),'1').'>Yes</option><option value="0"'.selected(setting($db,'allow_named_feedback'),'0').'>No</option></select></div>';
echo '<div class="form-group"><label>Demo/Synthetic Data Banner</label><select name="demo_mode"><option value="1"'.selected(setting($db,'demo_mode','1'),'1').'>Show</option><option value="0"'.selected(setting($db,'demo_mode','1'),'0').'>Hide after production data is loaded</option></select></div>';
echo '</div><button class="btn btn-primary" style="margin-top:14px">Save Settings</button></form></div>';

echo '<div class="form-card" style="margin-top:14px"><h2>Management Action Thresholds</h2><p class="muted">These configurable thresholds drive the Improve / Monitor / Strengthen decision-support actions. They are separate from the validated questionnaire interpretation: 1.00–1.75 Very Low, 1.76–2.50 Low, 2.51–3.25 High, 3.26–4.00 Very High. A change within the stability band is considered Stable.</p><form method="post">'.csrf_field().'<input type="hidden" name="action" value="decision_rules"><div class="form-grid">';
echo '<div class="form-group"><label>Lower Threshold</label><input type="number" step="0.01" min="1" max="4" name="threshold_low" value="'.e((string)$rule['low']).'"></div>';
echo '<div class="form-group"><label>Upper Threshold</label><input type="number" step="0.01" min="1" max="4" name="threshold_high" value="'.e((string)$rule['high']).'"></div>';
echo '<div class="form-group"><label>Stability Band (±)</label><input type="number" step="0.01" min="0" max="1" name="stability_band" value="'.e((string)$rule['band']).'"></div>';
echo '</div><button class="btn btn-primary" style="margin-top:14px">Save Decision Rules</button></form></div>';


