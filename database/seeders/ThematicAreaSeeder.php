<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ThematicAreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $thematicAreas = [
            [
                'name' => 'Digital Transformation',
                'slug' => 'digital-transformation',
                'description' => 'Ideas related to digital technologies, automation, and modernizing business processes',
                'sort_order' => 1,
            ],
            [
                'name' => 'Infrastructure Development',
                'slug' => 'infrastructure-development',
                'description' => 'Innovations in road construction, maintenance, and infrastructure management',
                'sort_order' => 2,
            ],
            [
                'name' => 'Safety & Security',
                'slug' => 'safety-security',
                'description' => 'Ideas to improve road safety, security measures, and emergency response',
                'sort_order' => 3,
            ],
            [
                'name' => 'Environmental Sustainability',
                'slug' => 'environmental-sustainability',
                'description' => 'Eco-friendly initiatives, green technologies, and sustainable practices',
                'sort_order' => 4,
            ],
            [
                'name' => 'Customer Experience',
                'slug' => 'customer-experience',
                'description' => 'Improving user experience, communication, and service delivery',
                'sort_order' => 5,
            ],
            [
                'name' => 'Operations & Efficiency',
                'slug' => 'operations-efficiency',
                'description' => 'Streamlining operations, improving efficiency, and resource optimization',
                'sort_order' => 6,
            ],
            [
                'name' => 'Data & Analytics',
                'slug' => 'data-analytics',
                'description' => 'Data-driven insights, analytics, and decision-making tools',
                'sort_order' => 7,
            ],
            [
                'name' => 'Other',
                'slug' => 'other',
                'description' => 'Ideas that don\'t fit into the above categories',
                'sort_order' => 8,
            ],
        ];

        foreach ($thematicAreas as $area) {
            \App\Models\ThematicArea::create($area);
        }
    }
}
