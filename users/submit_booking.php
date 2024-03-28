<?php
session_start();
include '../db.php'; // Adjust path as necessary

// Ensure this file handles the form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['customer_id'])) {
    $date = $_POST['date'];
    $time = $_POST['time'];
    $technician = $_POST['technician'];
    $tasks = $_POST['tasks'];
    $customer_id = $_SESSION['customer_id'];

    // Start transaction
    $mysqli->begin_transaction();

    try {
        // Insert into reservation table
        $reservationStmt = $mysqli->prepare("INSERT INTO reservation (reservation_day, reservation_time, customer_id, employee_id) VALUES (?, ?, ?, ?)");
        $reservationStmt->bind_param("ssii", $date, $time, $customer_id, $technician);
        $reservationStmt->execute();
        $reservation_id = $mysqli->insert_id;

        // Insert selected tasks - example for one task, loop for multiple
        foreach ($tasks as $task_id) {
            $taskStmt = $mysqli->prepare("INSERT INTO selected_tasks (reservation_id, task_id) VALUES (?, ?)");
            $taskStmt->bind_param("ii", $reservation_id, $task_id);
            $taskStmt->execute();
        }

        // Create an invoice - Example logic, adjust as per your requirement
        $invoiceStmt = $mysqli->prepare("INSERT INTO invoice (reservation_id, invoice_date, invoice_status) VALUES (?, CURRENT_DATE(), 'Pending')");
        $invoiceStmt->bind_param("i", $reservation_id);
        $invoiceStmt->execute();

        $mysqli->commit();
        header("Location: index.php"); // Redirect to the customer's dashboard
        exit;
    } catch (Exception $e) {
        $mysqli->rollback();
        echo "Booking failed: " . $e->getMessage();
    }
} else {
    echo "Invalid request.";
}


