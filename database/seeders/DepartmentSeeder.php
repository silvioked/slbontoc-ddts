<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates sample LGU departments
     */
    public function run(): void
    {
        $departments = [
            [
                'name' => 'BAC (Bids and Awards Committee)',
                'code' => 'BAC',
                'description' => 'Bids and Awards Committee',
                'is_active' => true,
            ],
            [
                'name' => 'BOMWASA',
                'code' => 'BOMWASA',
                'description' => 'BOMWASA',
                'is_active' => true,
            ],
            [
                'name' => 'DILG',
                'code' => 'DILG',
                'description' => 'Department of the Interior and Local Government',
                'is_active' => true,
            ],
            [
                'name' => 'GSO - Supply Office',
                'code' => 'GSO',
                'description' => 'General Services Office - Supply Office',
                'is_active' => true,
            ],
            [
                'name' => 'HRMO (Human Resource Management Office)',
                'code' => 'HRMO',
                'description' => 'Human Resource Management Office',
                'is_active' => true,
            ],
            [
                'name' => 'IAS (Internal Audit Service)',
                'code' => 'IAS',
                'description' => 'Internal Audit Service',
                'is_active' => true,
            ],
            [
                'name' => 'MAGSO (Municipal Agricultural Services Office)',
                'code' => 'MAGSO',
                'description' => 'Municipal Agricultural Services Office',
                'is_active' => true,
            ],
            [
                'name' => 'MASSO (Office of the Municipal Assessor)',
                'code' => 'MASSO',
                'description' => 'Office of the Municipal Assessor',
                'is_active' => true,
            ],
            [
                'name' => 'MCR (Office of the Municipal Civil Registrar)',
                'code' => 'MCR',
                'description' => 'Office of the Municipal Civil Registrar',
                'is_active' => true,
            ],
            [
                'name' => 'MDRRMO (Municipal Disaster Risk Reduction and Management Office)',
                'code' => 'MDRRMO',
                'description' => 'Municipal Disaster Risk Reduction and Management Office',
                'is_active' => true,
            ],
            [
                'name' => 'MENRO (Municipal Environment and Natural Resources Office)',
                'code' => 'MENRO',
                'description' => 'Municipal Environment and Natural Resources Office',
                'is_active' => true,
            ],
            [
                'name' => 'MEO (Office of the Municipal Engineer)',
                'code' => 'MEO',
                'description' => 'Office of the Municipal Engineer',
                'is_active' => true,
            ],
            [
                'name' => 'MOTORPOOL',
                'code' => 'MOTORPOOL',
                'description' => 'Motorpool',
                'is_active' => true,
            ],
            [
                'name' => 'MPDC (Office of the Municipal Planning and Development Coordinator)',
                'code' => 'MPDC',
                'description' => 'Office of the Municipal Planning and Development Coordinator',
                'is_active' => true,
            ],
            [
                'name' => 'MSWDO (Municipal Social Welfare and Development Office)',
                'code' => 'MSWDO',
                'description' => 'Municipal Social Welfare and Development Office',
                'is_active' => true,
            ],
            [
                'name' => 'MTO (Office of the Municipal Treasurer)',
                'code' => 'MTO',
                'description' => 'Office of the Municipal Treasurer',
                'is_active' => true,
            ],
            [
                'name' => 'Office of the Municipal Accountant',
                'code' => 'ACCTG',
                'description' => 'Office of the Municipal Accountant',
                'is_active' => true,
            ],
            [
                'name' => 'Office of the Municipal Budget Officer',
                'code' => 'BUDGET',
                'description' => 'Office of the Municipal Budget Officer',
                'is_active' => true,
            ],
            [
                'name' => 'Office of the Municipal Mayor',
                'code' => 'MAYOR',
                'description' => 'Office of the Municipal Mayor',
                'is_active' => true,
            ],
            [
                'name' => 'OSCA (Offices)',
                'code' => 'OSCA',
                'description' => 'OSCA Offices',
                'is_active' => true,
            ],
            [
                'name' => 'RHU (Rural Health Unit)',
                'code' => 'RHU',
                'description' => 'Rural Health Unit',
                'is_active' => true,
            ],
            [
                'name' => 'SB (Office of the Sangguniang Bayan)',
                'code' => 'SB',
                'description' => 'Office of the Sangguniang Bayan',
                'is_active' => true,
            ],
            [
                'name' => 'Tourism Office',
                'code' => 'TOURISM',
                'description' => 'Tourism Office',
                'is_active' => true,
            ],
        ];

        foreach ($departments as $department) {
            // Use updateOrCreate to avoid duplicates and update existing records
            Department::updateOrCreate(
                ['code' => $department['code']],
                $department
            );
        }

        $this->command->info('Departments created/updated successfully!');
    }
}

