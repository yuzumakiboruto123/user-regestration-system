<?php
// Stop execution if $users array is not passed to this file
if (!isset($users)) {
    exit; // Prevent table rendering without data
}
?>

<table class="table table-bordered table-striped"> <!-- Main user table -->
    <thead>
        <tr>
            <th style="width:5%;">ID</th> <!-- User ID -->
            <th style="width:15%;">Username</th> <!-- Username -->
            <th style="width:25%;">Email</th> <!-- Email address -->
            <th style="width:10%;">Role</th> <!-- User role -->
            <th style="width:10%;">Login Count</th> <!-- Number of logins -->
            <th style="width:20%;">Last Login</th> <!-- Timestamp of last login -->
            <th style="width:5%;">Status</th> <!-- Active/Inactive -->
            <th style="width:10%;">Actions</th> <!-- Edit/Delete -->
        </tr>
    </thead>

    <tbody>
    <?php if (empty($users)): ?> <!-- Check if user list is empty -->
        <tr>
            <td colspan="8" class="text-center">No users found.</td> <!-- Display empty message -->
        </tr>

    <?php else: ?> <!-- If users exist, loop them -->
        <?php foreach ($users as $u): ?> <!-- Iterate each user -->
            <tr>
                <td><?= htmlspecialchars($u['UserID']) ?></td> <!-- User ID -->
                <td><?= htmlspecialchars($u['Username']) ?></td> <!-- Username -->
                <td><?= htmlspecialchars($u['Email'] ?? '') ?></td> <!-- Email (safe fallback) -->
                <td><?= htmlspecialchars($u['Role']) ?></td> <!-- Role -->
                <td><?= htmlspecialchars($u['LoginCount']) ?></td> <!-- Login count -->
                <td><?= htmlspecialchars($u['LastLogin']) ?></td> <!-- Last login date -->

                <td>
                    <?php if (!empty($u['IsActive'])): ?> <!-- Active status -->
                        <span class="badge-status bg-success text-light">Active</span>
                    <?php else: ?> <!-- Inactive status -->
                        <span class="badge-status bg-secondary text-light">Inactive</span>
                    <?php endif; ?>
                </td>

                <td>
                    <!-- Edit button -->
                    <a class="btn btn-sm btn-primary mb-1" href="edit_user.php?id=<?= $u['UserID'] ?>">
                        Edit
                    </a>

                    <!-- Delete button -->
                    <a class="btn btn-sm btn-danger" href="delete_user.php?id=<?= $u['UserID'] ?>">
                        Delete
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
