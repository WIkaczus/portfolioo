<?php
header('Content-Type: application/json');

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'portfolio_db';

// Connect to database
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed']));
}

// Create videos table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    filename VARCHAR(255) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$conn->query($sql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle file upload
    if (isset($_FILES['video'])) {
        $file = $_FILES['video'];
        $title = $_POST['title'] ?? 'Untitled';
        $description = $_POST['description'] ?? '';
        
        // Check for errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            die(json_encode(['error' => 'File upload failed']));
        }

        // Validate file type
        $allowed_types = ['video/mp4', 'video/webm', 'video/ogg'];
        if (!in_array($file['type'], $allowed_types)) {
            die(json_encode(['error' => 'Invalid file type']));
        }

        // Generate unique filename
        $filename = uniqid() . '_' . $file['name'];
        $upload_path = 'uploads/' . $filename;

        // Create uploads directory if it doesn't exist
        if (!file_exists('uploads')) {
            mkdir('uploads', 0777, true);
        }

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            // Save to database
            $stmt = $conn->prepare("INSERT INTO videos (title, description, filename) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $title, $description, $filename);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Video uploaded successfully',
                    'id' => $conn->insert_id
                ]);
            } else {
                echo json_encode(['error' => 'Database error']);
            }
            $stmt->close();
        } else {
            echo json_encode(['error' => 'Failed to save file']);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch videos
    $sql = "SELECT * FROM videos ORDER BY upload_date DESC";
    $result = $conn->query($sql);
    
    $videos = [];
    while ($row = $result->fetch_assoc()) {
        $videos[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'filename' => $row['filename'],
            'upload_date' => $row['upload_date']
        ];
    }
    
    echo json_encode(['videos' => $videos]);
}

$conn->close();
?>
