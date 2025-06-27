<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "capstone");

$exam_id = $_POST['exam_id'];
$student_id = $_POST['student_id'];
$total_score = 0;
$max_score = 0;

$conn->query("INSERT INTO exam_taken (exam_id, student_id, datetime_started, datetime_submitted) VALUES ($exam_id, $student_id, NOW(), NOW())");
$take_id = $conn->insert_id;

$answers_map = [];
$res = $conn->query("SELECT q.question_id, q.correct_answer, q.question_type FROM exam_questions eq JOIN questions q ON eq.question_id = q.question_id WHERE eq.exam_id = $exam_id");
while ($row = $res->fetch_assoc()) {
  $answers_map[$row['question_id']] = [
    "correct" => strtolower(trim($row['correct_answer'])),
    "type" => $row['question_type']
  ];
}

foreach ($_POST as $key => $value) {
  if (strpos($key, "answer_") === 0) {
    $question_id = str_replace("answer_", "", $key);
    $answer_text = strtolower(trim($value));
    $stmt = $conn->prepare("INSERT INTO exam_answers (take_id, question_id, answer_text) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $take_id, $question_id, $value);
    $stmt->execute();

    $max_score += 1;
    if (isset($answers_map[$question_id])) {
      $correct = $answers_map[$question_id]['correct'];
      $type = $answers_map[$question_id]['type'];
      if (in_array($type, ['multiple_choice', 'true_false']) && $correct === $answer_text) {
        $total_score += 1;
      }
    }
  }
}

$score = ($max_score > 0) ? ($total_score / $max_score) * 100 : 0;
$conn->query("UPDATE exam_taken SET score = $score WHERE take_id = $take_id");

echo json_encode(["status" => "success", "message" => "Exam submitted. Score: $score"]);
?>