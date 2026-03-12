<?php
session_start();
require_once 'connect.php'; // Assuming this file contains your database connection

// Check if user is logged in
// if (!isset($_SESSION['user_email'])) {
//     header("Location: homeWithoutLogin.html");
//     exit();
// }

$user_email = $_SESSION['email'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complaint_type'], $_POST['complaint_text'])) {
    $complaint_type = $_POST['complaint_type'];
    $complaint_text = $_POST['complaint_text'];
    
    // Insert new complaint
    $stmt = $conn->prepare("INSERT INTO complaints (user_email, complaint_type, complaint_text) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $user_email, $complaint_type, $complaint_text);
    
    if ($stmt->execute()) {
        $success_message = "Complaint submitted successfully!";
    } else {
        $error_message = "Error submitting complaint. Please try again.";
    }
}

$previous_complaints = [];
$stmt = $conn->prepare("SELECT id, complaint_type, complaint_text, status, created_at FROM complaints WHERE user_email = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $previous_complaints[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workplace Complaint Registration</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-blue-50 min-h-screen p-4">
    <div class="max-w-4xl mx-auto">
        <!-- Complaint Form -->
        <div class="w-full bg-white rounded-lg shadow-md overflow-hidden mb-8">
            <div class="bg-blue-600 py-4 px-6">
                <h1 class="text-2xl font-bold text-white">Register a Complaint</h1>
                <p class="text-blue-100">We're here to help resolve your workplace issues</p>
            </div>
            
            <form class="p-6 space-y-6" method="post">
                <?php if (isset($success_message)): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline"><?php echo $success_message; ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline"><?php echo $error_message; ?></span>
                    </div>
                <?php endif; ?>
                
                <div>
                    <label for="complaintType" class="block text-sm font-medium text-gray-700 mb-1">Complaint Type</label>
                    <select id="complaintType" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" name="complaint_type" required>
                        <option value="" disabled selected>Select complaint type</option>
                        <option value="wage">Unpaid Wages or Overtime</option>
                        <option value="harassment">Workplace Harassment or Discrimination</option>
                        <option value="safety">Unsafe Working Conditions</option>
                        <option value="retaliation">Retaliation for Complaint or Whistleblowing</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div>
                    <label for="complaintDetails" class="block text-sm font-medium text-gray-700 mb-1">Complaint Details</label>
                    <textarea 
                        id="complaintDetails" 
                        rows="8" 
                        maxlength="1750" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Please describe your complaint in detail (350 words maximum)..." 
                        name="complaint_text" required></textarea>
                    <p class="text-xs text-gray-500 mt-1">
                        <span id="charCount">0</span>/1750 characters (approx. 350 words)
                    </p>
                </div>
                
                <div class="flex items-center justify-end">
                    <input type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" value="Submit Complaint">
                </div>
            </form>
        </div>
        
        <!-- Previous Complaints Section -->
        <div class="w-full bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-blue-600 py-4 px-6">
                <h2 class="text-xl font-bold text-white">Your Previous Complaints</h2>
                <p class="text-blue-100">View the status of your submitted complaints</p>
            </div>
            
            <div class="p-6">
                <?php if (empty($previous_complaints)): ?>
                    <p class="text-gray-500">You haven't submitted any complaints yet.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($previous_complaints as $complaint): ?>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-medium text-gray-900 capitalize">
                                            <?php 
                                                $types = [
                                                    'wage' => 'Unpaid Wages or Overtime',
                                                    'harassment' => 'Workplace Harassment',
                                                    'safety' => 'Unsafe Working Conditions',
                                                    'retaliation' => 'Retaliation'
                                                ];
                                                echo $types[$complaint['complaint_type']] ?? ucfirst($complaint['complaint_type']);
                                            ?>
                                        </h3>
                                        <p class="text-sm text-gray-500">
                                            Submitted on <?php echo date('M j, Y g:i A', strtotime($complaint['created_at'])); ?>
                                        </p>
                                    </div>
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        <?php 
                                            echo $complaint['status'] === 'Resolved' ? 'bg-green-100 text-green-800' : 
                                                 ($complaint['status'] === 'In Progress' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800');
                                        ?>">
                                        <?php echo $complaint['status']; ?>
                                    </span>
                                </div>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-700"><?php echo nl2br(htmlspecialchars($complaint['complaint_text'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const textarea = document.getElementById('complaintDetails');
        const charCount = document.getElementById('charCount');
        
        textarea.addEventListener('input', function() {
            const currentLength = this.value.length;
            charCount.textContent = currentLength;
            
            if (currentLength > 1600) {
                charCount.classList.add('text-red-500');
                charCount.classList.remove('text-gray-500');
            } else {
                charCount.classList.remove('text-red-500');
                charCount.classList.add('text-gray-500');
            }
        });
    </script>
</body>
</html>