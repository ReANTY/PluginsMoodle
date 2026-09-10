<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Adaptive Learning AI';
$string['adaptive_learning_ai:addinstance'] = 'Add a new Adaptive Learning AI block';
$string['adaptive_learning_ai:myaddinstance'] = 'Add a new Adaptive Learning AI block to Dashboard';
$string['notenrolled'] = 'You are not enrolled in this course.';

// Settings
$string['setting_ai_heading'] = 'Google Gemini AI Settings';
$string['setting_ai_heading_desc'] = 'Configure Google Gemini API integration for adaptive learning recommendations and microlearning tutor.';
$string['setting_gemini_apikey'] = 'Gemini API Key';
$string['setting_gemini_apikey_desc'] = 'Enter your Google Gemini API key from Google AI Studio. Leave empty to use offline fallback units only.';
$string['setting_gemini_model'] = 'Gemini Model';
$string['setting_gemini_model_desc'] = 'Choose the generative AI model to use for recommendations and interactive tutoring.';
$string['setting_threshold_heading'] = 'Cognitive Level Thresholds (Adaptive Learning Path)';
$string['setting_threshold_heading_desc'] = 'Configure cognitive evaluation score cutoffs used to dynamically determine weekly student learning paths (Primary, Intermediate, Expert).';
$string['setting_primary_threshold'] = 'Primary Threshold (%)';
$string['setting_primary_threshold_desc'] = 'Scores strictly below this percentage classify the student into the Primary level (foundational / beginner). Default: 70.';
$string['setting_expert_threshold'] = 'Expert Threshold (%)';
$string['setting_expert_threshold_desc'] = 'Scores equal to or above this percentage classify the student into the Expert level (advanced / mastery). Default: 85. Scores in between fall into Intermediate.';
$string['setting_remedial_threshold'] = 'Remedial Threshold (%)';
$string['setting_remedial_threshold_desc'] = 'Scores strictly below this value are classified as Low / Remedial (default: 70).';
$string['setting_advanced_threshold'] = 'Advanced Threshold (%)';
$string['setting_advanced_threshold_desc'] = 'Scores equal to or above this value are classified as High / Advanced (default: 90).';

// Reports & block UI
$string['report_quiz'] = 'Quiz Report';
$string['report_teacher'] = 'Teacher Quiz Report';
$string['report_student'] = 'My Quiz Scores';
$string['student_name'] = 'Student Name';
$string['student_score'] = 'Score';
$string['student_level'] = 'Level';
$string['student_status'] = 'Status';
$string['level_primary'] = 'Primary';
$string['level_intermediate'] = 'Intermediate';
$string['level_expert'] = 'Expert';
$string['level_low'] = 'Low';
$string['level_medium'] = 'Medium';
$string['level_high'] = 'High';
$string['report_student_list'] = 'Student List';
$string['report_graph_title'] = 'Student Scores';
$string['report_graph_series'] = 'Score (%)';
$string['back_to_list'] = 'Back to List';
$string['quiz_detail'] = 'Quiz Detail';
$string['student_answer'] = 'Student Answer';
$string['correct_answer'] = 'Correct Answer';
$string['question_score'] = 'Question Score';
$string['quiz_date_time'] = 'Quiz Date and Time';
$string['quiz_duration'] = 'Duration';
$string['quiz_status'] = 'Quiz Status';
$string['question_text'] = 'Question';
$string['no_quiz_data'] = 'No quiz data available';
$string['average_score'] = 'Average Score';
$string['highest_score'] = 'Highest Score';
$string['total_quiz'] = 'Total Quiz';
$string['student_progress_chart'] = 'Student Progress Chart';