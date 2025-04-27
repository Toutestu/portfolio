<?php
// データベース接続情報
$host = 'mysql320.phy.lolipop.lan'; // ロリポップが提供するデータベースホスト名
$dbname = 'LAA1659499-test';        // データベース名
$username = 'LAA1659499';           // データベースユーザー名
$password = 'test';                 // データベースパスワード

// デバッグログファイルのパス
$logFile = __DIR__ . '/logs/debug.log';

// デバッグログにエラーメッセージを記録する関数
function logError($message, $data = null) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    if ($data) {
        $logMessage .= ' - ' . json_encode($data);
    }
    file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
}

// データベース接続
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // タイムゾーンをJSTに設定
    $pdo->exec("SET time_zone = '+09:00'");
} catch (PDOException $e) {
    logError('データベース接続エラー', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'データベース接続エラー']);
    exit;
}

// POSTデータを受け取る
$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    // 必須項目の検証
    $requiredFields = ['randomString', 'nameParent', 'nameKid', 'grade', 'gradeDetail', 'address', 'phone', 'email', 'timestamp'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            logError("必須項目が不足しています: $field", $data);
            http_response_code(400); // Bad Request
            echo json_encode(['status' => 'error', 'message' => "必須項目が不足しています: $field"]);
            exit;
        }
    }

    // 必須項目のチェック
    if (!empty($data['randomString']) && !empty($data['nameParent']) && !empty($data['nameKid'])) {
        // 日本時間に変換
        $data['timestamp'] = (new DateTime($data['timestamp']))->setTimezone(new DateTimeZone('Asia/Tokyo'))->format('Y-m-d H:i:s');

        try {
            $sql = "INSERT INTO application (randomString, name_parent, name_kid, grade, grade_detail, address, phone, email, timestamp)
                    VALUES (:randomString, :nameParent, :nameKid, :grade, :gradeDetail, :address, :phone, :email, :timestamp)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':randomString' => $data['randomString'],
                ':nameParent' => $data['nameParent'],
                ':nameKid' => $data['nameKid'],
                ':grade' => $data['grade'],
                ':gradeDetail' => $data['gradeDetail'],
                ':address' => $data['address'],
                ':phone' => $data['phone'],
                ':email' => $data['email'],
                ':timestamp' => $data['timestamp'] // 日本時間に変換済み
            ]);

            // 成功レスポンスを返す（ログは記録しない）
            http_response_code(200);
            echo json_encode(['message' => 'データが正常に保存されました。']);
        } catch (PDOException $e) {
            logError('データベースエラー', ['error' => $e->getMessage(), 'data' => $data]);
            http_response_code(500); // Internal Server Error
            echo json_encode(['status' => 'error', 'message' => 'データベースエラー']);
        }
    } else {
        logError('必須項目が不足しています', $data);
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => '必須項目が不足しています。']);
    }
} else {
    logError('無効なリクエスト: POSTデータが空です');
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => '無効なリクエストです。']);
}
?>