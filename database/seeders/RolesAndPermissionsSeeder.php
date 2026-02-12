<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Product permissions
            'view products',
            'create products',
            'edit products',
            'delete products',

            // Category permissions
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',

            // Order permissions
            'view orders',
            'create orders',
            'edit orders',
            'delete orders',
            'manage orders',

            // User permissions
            'view users',
            'create users',
            'edit users',
            'delete users',
            'manage users',

            // Discount permissions
            'view discounts',
            'create discounts',
            'edit discounts',
            'delete discounts',

            // Client permissions
            'view clients',
            'create clients',
            'edit clients',
            'delete clients',

            // Review permissions
            'view reviews',
            'create reviews',
            'edit reviews',
            'delete reviews',
            'moderate reviews',

            // Settings permissions
            'view settings',
            'edit settings',

            // City permissions
            'view cities',
            'create cities',
            'edit cities',
            'delete cities',

            // Package deal permissions
            'view packages',
            'create packages',
            'edit packages',
            'delete packages',

            // Role & Permission management
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
            'assign roles',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Super Admin role - has all permissions
        $superAdmin = Role::create(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin role
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo([
            'view products', 'create products', 'edit products', 'delete products',
            'view categories', 'create categories', 'edit categories', 'delete categories',
            'view orders', 'edit orders', 'manage orders',
            'view discounts', 'create discounts', 'edit discounts', 'delete discounts',
            'view clients', 'create clients', 'edit clients', 'delete clients',
            'view reviews', 'moderate reviews',
            'view cities', 'edit cities',
            'view packages', 'create packages', 'edit packages', 'delete packages',
            'view settings',
        ]);

        // Manager role
        $manager = Role::create(['name' => 'manager']);
        $manager->givePermissionTo([
            'view products', 'create products', 'edit products',
            'view categories',
            'view orders', 'edit orders', 'manage orders',
            'view discounts', 'create discounts', 'edit discounts',
            'view clients', 'create clients', 'edit clients',
            'view reviews', 'moderate reviews',
            'view packages',
        ]);

        // Customer Service role
        $customerService = Role::create(['name' => 'customer-service']);
        $customerService->givePermissionTo([
            'view products',
            'view categories',
            'view orders', 'edit orders',
            'view clients', 'edit clients',
            'view reviews',
        ]);

        // Customer role (default for regular users)
        $customer = Role::create(['name' => 'customer']);
        $customer->givePermissionTo([
            'view products',
            'view categories',
            'create orders',
            'create reviews',
        ]);

        // Create a super admin user if it doesn't exist
        $superAdminUser = User::where('email', 'admin@samer.com')->first();
        if (!$superAdminUser) {
            $superAdminUser = User::create([
                'name' => 'Super Admin',
                'email' => 'admin@samer.com',
                'password' => Hash::make('admin123456'),
                'email_verified_at' => now(),
            ]);
        }
        $superAdminUser->assignRole('super-admin');

        $this->command->info('Roles and permissions seeded successfully!');
        $this->command->info('Super Admin created: admin@samer.com / admin123456');
    }
}
