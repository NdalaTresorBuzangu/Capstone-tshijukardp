<?php
/**
 * User Controller
 * 
 * Handles user-related business logic.
 * Used by both web views and API endpoints.
 */

namespace App\Controllers;

use App\Models\User;

class UserController
{
    private User $userModel;
    private \mysqli $db;

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
        $this->userModel = new User($db);
    }

    /**
     * Get all users
     */
    public function getAll(): array
    {
        return [
            'success' => true,
            'users' => $this->userModel->getAll()
        ];
    }

    /**
     * Get user by ID
     */
    public function getById(int $userId): array
    {
        $user = $this->userModel->findById($userId);

        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        return ['success' => true, 'user' => $user];
    }

    /**
     * Get users by role
     */
    public function getByRole(string $role): array
    {
        return [
            'success' => true,
            'users' => $this->userModel->getByRole($role)
        ];
    }

    /**
     * Update user profile
     */
    public function updateProfile(int $userId, array $data): array
    {
        // Validate user exists
        $user = $this->userModel->findById($userId);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        // If email is being changed, check uniqueness
        if (isset($data['email']) && $data['email'] !== $user['userEmail']) {
            if ($this->userModel->emailExists($data['email'], $userId)) {
                return ['success' => false, 'message' => 'Email already in use.'];
            }
        }

        $success = $this->userModel->update($userId, $data);

        return $success
            ? ['success' => true, 'message' => 'Profile updated.']
            : ['success' => false, 'message' => 'Failed to update profile.'];
    }

    /**
     * Update password
     */
    public function updatePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        $user = $this->userModel->findByEmail($this->userModel->findById($userId)['userEmail'] ?? '');

        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        // Verify current password
        if (!password_verify($currentPassword, $user['userPassword'] ?? '')) {
            return ['success' => false, 'message' => 'Current password is incorrect.'];
        }

        // Validate new password
        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => 'New password must be at least 6 characters.'];
        }

        $success = $this->userModel->update($userId, ['password' => $newPassword]);

        return $success
            ? ['success' => true, 'message' => 'Password updated.']
            : ['success' => false, 'message' => 'Failed to update password.'];
    }

    /**
     * Delete user (admin only)
     */
    public function delete(int $userId): array
    {
        $success = $this->userModel->delete($userId);

        return $success
            ? ['success' => true, 'message' => 'User deleted.']
            : ['success' => false, 'message' => 'Failed to delete user.'];
    }

    /**
     * Get user model
     */
    public function getUserModel(): User
    {
        return $this->userModel;
    }
}
