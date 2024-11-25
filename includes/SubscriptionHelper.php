<?php
class SubscriptionHelper {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Check if user has an active subscription
     */
    public function hasActiveSubscription($userId) {
        $stmt = $this->conn->prepare("
            SELECT 1 FROM user_subscriptions 
            WHERE user_id = ? 
            AND status = 'active' 
            AND end_date > NOW() 
            LIMIT 1
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
    
    /**
     * Get user's current subscription details
     */
    public function getCurrentSubscription($userId) {
        $stmt = $this->conn->prepare("
            SELECT us.*, sp.name as plan_name, sp.price, sp.duration_months 
            FROM user_subscriptions us
            JOIN subscription_plans sp ON us.plan_id = sp.id
            WHERE us.user_id = ? 
            AND us.status = 'active' 
            AND us.end_date > NOW()
            ORDER BY us.end_date DESC 
            LIMIT 1
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Get all available subscription plans
     */
    public function getSubscriptionPlans() {
        $stmt = $this->conn->prepare("SELECT * FROM subscription_plans ORDER BY duration_months");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Subscribe user to a plan
     */
    public function subscribeToPlan($userId, $planId) {
        try {
            $this->conn->begin_transaction();
            
            // Get plan details
            $stmt = $this->conn->prepare("SELECT * FROM subscription_plans WHERE id = ?");
            $stmt->bind_param("i", $planId);
            $stmt->execute();
            $plan = $stmt->get_result()->fetch_assoc();
            
            if (!$plan) {
                throw new Exception("Invalid plan selected");
            }
            
            // Calculate subscription dates
            $startDate = date('Y-m-d H:i:s');
            $endDate = date('Y-m-d H:i:s', strtotime("+{$plan['duration_months']} months"));
            
            // Insert subscription
            $stmt = $this->conn->prepare("
                INSERT INTO user_subscriptions (user_id, plan_id, start_date, end_date) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("iiss", $userId, $planId, $startDate, $endDate);
            $stmt->execute();
            
            $subscriptionId = $this->conn->insert_id;
            
            // Insert payment record
            $stmt = $this->conn->prepare("
                INSERT INTO subscription_payments (user_id, subscription_id, amount, payment_method) 
                VALUES (?, ?, ?, 'credit_card')
            ");
            $stmt->bind_param("iid", $userId, $subscriptionId, $plan['price']);
            $stmt->execute();
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Subscription error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user has reached free tier limits
     */
    public function checkFreeTierLimits($userId, $limitType) {
        switch ($limitType) {
            case 'goals':
                $stmt = $this->conn->prepare("
                    SELECT COUNT(*) as count 
                    FROM goals 
                    WHERE user_id = ? 
                    AND is_archived = 0
                ");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                return $result['count'] >= 5; // Free tier limit of 5 goals
                
            default:
                return false;
        }
    }
    
    /**
     * Cancel user's subscription
     */
    public function cancelSubscription($userId) {
        $stmt = $this->conn->prepare("
            UPDATE user_subscriptions 
            SET status = 'cancelled' 
            WHERE user_id = ? 
            AND status = 'active'
        ");
        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    }
}