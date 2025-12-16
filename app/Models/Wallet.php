<?php
namespace App\Models;

use App\Core\ConnectDB;
use PDO;

class Wallet
{
    private $db;

    public function __construct()
    {
        $this->db = (new ConnectDB())->getConnection();
    }

    // [FIXED] Đổi tên từ getAllWallets -> getUserWallets để khớp với Controller
    public function getUserWallets($userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM user_wallets WHERE user_id = ?");
        $stmt->execute([$userId]);
        $wallets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // [QUAN TRỌNG] Nếu chưa có ví nào (user mới hoặc lỗi), tự động tạo đủ 6 hũ
        if (empty($wallets)) {
            $this->initWallets($userId);
            // Lấy lại danh sách sau khi tạo
            $stmt->execute([$userId]);
            $wallets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $wallets;
    }

    // Hàm phụ: Khởi tạo 6 hũ mặc định
    public function initWallets($userId)
    {
        $jarCodes = ['nec', 'ffa', 'ltss', 'edu', 'play', 'give'];
        $sql = "INSERT IGNORE INTO user_wallets (user_id, jar_code, balance) VALUES (?, ?, 0)";
        $stmt = $this->db->prepare($sql);

        foreach ($jarCodes as $code) {
            $stmt->execute([$userId, $code]);
        }
    }

    // Lấy thông tin 1 ví cụ thể (giữ nguyên)
    public function getWallet($userId, $jarCode)
    {
        $stmt = $this->db->prepare("SELECT * FROM user_wallets WHERE user_id = ? AND jar_code = ?");
        $stmt->execute([$userId, $jarCode]);
        $wallet = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$wallet) {
            $this->db->prepare("INSERT INTO user_wallets (user_id, jar_code, balance) VALUES (?, ?, 0)")
                     ->execute([$userId, $jarCode]);
            return ['balance' => 0, 'jar_code' => $jarCode];
        }
        return $wallet;
    }

    // Lấy số dư dạng Key-Value (giữ nguyên, nhưng gọi getUserWallets)
    public function getWalletBalances($userId)
    {
        $wallets = $this->getUserWallets($userId); // [FIXED] Gọi đúng tên hàm mới
        $balances = [];
        foreach ($wallets as $w) {
            $balances[$w['jar_code']] = $w['balance'];
        }
        return $balances;
    }

    // Cộng tiền (giữ nguyên)
    public function addMoney($userId, $jarCode, $amount)
    {
        $this->getWallet($userId, $jarCode); // Đảm bảo ví tồn tại
        $sql = "UPDATE user_wallets SET balance = balance + ? WHERE user_id = ? AND jar_code = ?";
        return $this->db->prepare($sql)->execute([$amount, $userId, $jarCode]);
    }

    // Trừ tiền (giữ nguyên)
    public function subtractMoney($userId, $jarCode, $amount)
    {
        $sql = "UPDATE user_wallets SET balance = balance - ? WHERE user_id = ? AND jar_code = ?";
        return $this->db->prepare($sql)->execute([$amount, $userId, $jarCode]);
    }
}