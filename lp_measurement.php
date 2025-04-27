<?php
// デバッグ用エラーログを有効化
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// デバッグログファイルのパス
$logFile = __DIR__ . '/logs/debug.txt';

if (!file_exists($logFile)) {
    file_put_contents($logFile, "ログファイルが作成されました。" . PHP_EOL, FILE_APPEND);
}

// デバッグログにメッセージを記録する関数
function logDebug($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

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

// データベース接続情報
$host = 'mysql320.phy.lolipop.lan'; // ロリポップが提供するデータベースホスト名
$dbname = 'LAA1659499-test';        // データベース名
$username = 'LAA1659499';           // データベースユーザー名
$password = 'test';                 // データベースパスワード

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
    $randomString = $data['randomString'] ?? null;
    $slideID = $data['slideID'] ?? null;
    $startTime = $data['startTime'] ?? null;
    $endTime = $data['endTime'] ?? null;
    $duration = $data['duration'] ?? null;
    $status = $data['status'] ?? null;
    $referrer = $data['referrer'] ?? null;

    // referrerがNULLの場合は空文字列に変換
    if ($referrer === null) {
        $referrer = '';
    }

    // 必須項目のチェック
    if (!empty($randomString) && !empty($status)) {
        try {
            $sql = "INSERT INTO lp_measurement (randomString, slideID, startTime, endTime, duration, status, referrer)
                    VALUES (:randomString, :slideID, :startTime, :endTime, :duration, :status, :referrer)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':randomString' => $randomString,
                ':slideID' => $slideID,
                ':startTime' => $startTime,
                ':endTime' => $endTime,
                ':duration' => $duration,
                ':status' => $status,
                ':referrer' => $referrer // 空文字列に変換済み
            ]);

            // 成功レスポンスを返す
            echo json_encode(['status' => 'success', 'message' => 'データが正常に保存されました。']);
        } catch (PDOException $e) {
            logError('データ保存エラー', ['error' => $e->getMessage(), 'data' => $data]);
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'データ保存中にエラーが発生しました']);
        }
    } else {
        logError('必須項目が不足しています: randomStringまたはstatusが空です', $data);
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => '必須項目が不足しています。']);
    }
} else {
    logError('無効なリクエスト: POSTデータが空です');
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => '無効なリクエストです。']);
}
?>