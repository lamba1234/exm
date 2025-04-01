<?php  
  class UserBudget extends Base {
    function __construct($pdo) {
      $this->pdo = $pdo;
    }

    // To check validity of the set budget
    public function budget_validity_checker($UserId){
      $stmt = $this->pdo->prepare("SELECT EXTRACT(MONTH FROM date) AS mon FROM budget WHERE user_id = :user");
      $stmt->bindParam(":user", $UserId, PDO::PARAM_INT);
      $stmt->execute();
      $r = $stmt->fetch(PDO::FETCH_OBJ);
      if($r == NULL)
      {
        return true;
      }
      else
      {
        $val1 = $r->mon;
      }
      
      $stmt2 = $this->pdo->prepare("SELECT EXTRACT(MONTH FROM CURRENT_TIMESTAMP()) AS current");
      $stmt2->execute();
      $z = $stmt2->fetch(PDO::FETCH_OBJ);
      $val2 = $z->current;

      if($val1 === $val2)
      {
        return true;
      }
      else
      {
        return false;
      }
    }

    // To set the budget
    public function setbudget($UserId, $budget) {
      $stmt = $this->pdo->prepare("INSERT INTO budget(user_id, amount, date) VALUES(:user, :amount, CURRENT_DATE())");
      $stmt->bindParam(":user", $UserId, PDO::PARAM_INT);
      $stmt->bindParam(":amount", $budget, PDO::PARAM_INT);
      $stmt->execute();
    }

    // To check the current budget
    public function checkbudget($UserId) {
      $stmt = $this->pdo->prepare("SELECT amount AS currentbudget FROM budget WHERE user_id = :user AND MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())");
      $stmt->bindParam(":user", $UserId, PDO::PARAM_INT);
      $stmt->execute();
      $rows = $stmt->fetch(PDO::FETCH_OBJ);
      if($rows == NULL)
      {
        return NULL;
      }
      else
      {
        return $rows->currentbudget;
      }
    }

    // To update current budget
    public function updatebudget($UserId, $budget) {
      $stmt = $this->pdo->prepare("UPDATE budget SET amount = :amount, date = CURRENT_DATE() WHERE user_id = :user AND MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())");
      $stmt->bindParam(":user", $UserId, PDO::PARAM_INT);
      $stmt->bindParam(":amount", $budget, PDO::PARAM_INT);
      $stmt->execute();
    }
    
    // To delete the monthly budget record (Once the month changes)
    public function del_budget_record($UserId){
      $stmt = $this->pdo->prepare("DELETE FROM budget WHERE user_id = :user");
      $stmt->bindParam(":user", $UserId, PDO::PARAM_INT);
      $stmt->execute();
    }

    // To get remaining budget
    public function get_remaining_budget($UserId) {
      // Get current budget
      $stmt = $this->pdo->prepare("SELECT amount FROM budget WHERE user_id = :user AND MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())");
      $stmt->bindParam(":user", $UserId, PDO::PARAM_INT);
      $stmt->execute();
      $budget_result = $stmt->fetch(PDO::FETCH_OBJ);
      
      if ($budget_result === NULL) {
        return NULL;
      }
      
      $current_budget = $budget_result->amount;

      // Get total expenses for current month
      $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses_user WHERE user_id = :user AND MONTH(expense_date) = MONTH(CURRENT_DATE()) AND YEAR(expense_date) = YEAR(CURRENT_DATE())");
      $stmt->bindParam(":user", $UserId, PDO::PARAM_INT);
      $stmt->execute();
      $expense_result = $stmt->fetch(PDO::FETCH_OBJ);
      $total_expenses = $expense_result->total;

      // Debug: Print the values
      error_log("Budget Debug - User ID: " . $UserId);
      error_log("Current Budget: " . $current_budget);
      error_log("Total Expenses: " . $total_expenses);
      error_log("Remaining Budget: " . ($current_budget - $total_expenses));

      // Also check if there are any expenses in the table
      $check_stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM expenses_user WHERE user_id = :user");
      $check_stmt->bindParam(":user", $UserId, PDO::PARAM_INT);
      $check_stmt->execute();
      $count_result = $check_stmt->fetch(PDO::FETCH_OBJ);
      error_log("Total number of expenses for user: " . $count_result->count);

      return $current_budget - $total_expenses;
    }

  }
?>