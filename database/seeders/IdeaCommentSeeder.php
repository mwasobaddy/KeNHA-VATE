<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Idea;
use App\Models\ThematicArea;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class IdeaCommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing users and thematic areas
        $users = User::all();
        $thematicAreas = ThematicArea::all();

        if ($users->isEmpty() || $thematicAreas->isEmpty()) {
            $this->command->warn('No users or thematic areas found. Please run UserSeeder and ThematicAreaSeeder first.');
            return;
        }

        // Create sample ideas
        $ideas = [
            [
                'idea_title' => 'AI-Powered Traffic Management System',
                'thematic_area_id' => $thematicAreas->where('slug', 'digital-transformation')->first()?->id ?? $thematicAreas->first()->id,
                'abstract' => 'Implement an AI-driven traffic management system that uses machine learning algorithms to optimize traffic flow, reduce congestion, and improve road safety across Kenya\'s highway network.',
                'problem_statement' => 'Kenya\'s highways experience significant traffic congestion during peak hours, leading to increased travel times, fuel consumption, and accident rates. Current traffic management systems are outdated and lack predictive capabilities.',
                'proposed_solution' => 'Deploy AI-powered cameras and sensors along major highways that collect real-time traffic data. Machine learning algorithms will analyze patterns and predict traffic congestion, automatically adjusting traffic signals and providing alternative route suggestions to drivers via mobile apps.',
                'cost_benefit_analysis' => 'Initial investment: KSh 500M. Expected benefits: 30% reduction in travel times, 25% decrease in fuel consumption, 40% reduction in accident rates. ROI expected within 2 years through fuel savings and increased productivity.',
                'declaration_of_interests' => 'No conflicts of interest. This proposal is based on publicly available research and industry best practices.',
                'original_idea_disclaimer' => true,
                'collaboration_enabled' => true,
                'team_effort' => true,
                'team_members' => [
                    ['name' => 'Dr. Sarah Kimani', 'role' => 'AI Specialist', 'organization' => 'University of Nairobi'],
                    ['name' => 'Eng. David Mwangi', 'role' => 'Traffic Engineer', 'organization' => 'KeNHA'],
                ],
                'status' => 'submitted',
            ],
            [
                'idea_title' => 'Solar-Powered Highway Lighting System',
                'thematic_area_id' => $thematicAreas->where('slug', 'environmental-sustainability')->first()?->id ?? $thematicAreas->first()->id,
                'abstract' => 'Replace traditional highway lighting with solar-powered LED systems to reduce energy costs and carbon emissions while improving visibility and safety.',
                'problem_statement' => 'Current highway lighting systems consume significant electricity and contribute to carbon emissions. Power outages in rural areas leave highways dangerously dark, increasing accident risks.',
                'proposed_solution' => 'Install solar-powered LED lighting systems along all major highways. Each light pole will have integrated solar panels, batteries for nighttime operation, and smart sensors that adjust brightness based on traffic and weather conditions.',
                'cost_benefit_analysis' => 'Initial cost: KSh 2.5B for complete highway network. Annual savings: KSh 800M in electricity costs. Carbon reduction: 50,000 tons CO2 annually. Payback period: 3.5 years.',
                'declaration_of_interests' => 'No personal financial interests. Author has previously consulted on renewable energy projects.',
                'original_idea_disclaimer' => true,
                'collaboration_enabled' => true,
                'team_effort' => false,
                'team_members' => null,
                'status' => 'submitted',
            ],
            [
                'idea_title' => 'Mobile Road Maintenance App',
                'thematic_area_id' => $thematicAreas->where('slug', 'operations-efficiency')->first()?->id ?? $thematicAreas->first()->id,
                'abstract' => 'Develop a mobile application that allows KeNHA staff to report road damages in real-time, track maintenance progress, and coordinate repair teams more efficiently.',
                'problem_statement' => 'Road damage reporting is currently done through paper forms and phone calls, leading to delays in repairs and inefficient resource allocation. Field staff lack tools to document and prioritize maintenance needs.',
                'proposed_solution' => 'Create a mobile app for iOS and Android that allows staff to photograph road damages, GPS-tag locations, categorize severity, and submit reports instantly. The system will automatically prioritize repairs and assign work orders to maintenance teams.',
                'cost_benefit_analysis' => 'Development cost: KSh 50M. Expected benefits: 50% faster response times, 30% reduction in maintenance costs through better prioritization, improved road conditions leading to fewer accidents.',
                'declaration_of_interests' => 'No conflicts of interest. This is a standard digital transformation initiative.',
                'original_idea_disclaimer' => true,
                'collaboration_enabled' => false,
                'team_effort' => false,
                'team_members' => null,
                'status' => 'submitted',
            ],
            [
                'idea_title' => 'Automated Toll Collection System',
                'thematic_area_id' => $thematicAreas->where('slug', 'digital-transformation')->first()?->id ?? $thematicAreas->first()->id,
                'abstract' => 'Implement a modern electronic toll collection system to replace manual toll booths, reduce congestion, and improve revenue collection efficiency.',
                'problem_statement' => 'Manual toll collection creates traffic bottlenecks and is prone to errors. Revenue leakage occurs due to manual processes and lack of proper tracking.',
                'proposed_solution' => 'Deploy RFID-based electronic toll collection with mobile payment integration. Vehicles will be equipped with transponders, and drivers can pay via mobile money, cards, or automatic deduction from pre-paid accounts.',
                'cost_benefit_analysis' => 'System cost: KSh 1.2B. Expected revenue increase: 40% through reduced leakage. Traffic flow improvement: 60% reduction in toll booth delays.',
                'declaration_of_interests' => 'No conflicts of interest. Similar systems have been successfully implemented worldwide.',
                'original_idea_disclaimer' => false,
                'collaboration_enabled' => true,
                'team_effort' => true,
                'team_members' => [
                    ['name' => 'Mr. James Oduya', 'role' => 'Finance Director', 'organization' => 'KeNHA'],
                    ['name' => 'Ms. Grace Wanjiku', 'role' => 'IT Manager', 'organization' => 'KeNHA'],
                ],
                'status' => 'draft',
            ],
            [
                'idea_title' => 'Emergency Response Drone Network',
                'thematic_area_id' => $thematicAreas->where('slug', 'safety-security')->first()?->id ?? $thematicAreas->first()->id,
                'abstract' => 'Establish a network of drones for rapid emergency response, accident assessment, and medical supply delivery along highways.',
                'problem_statement' => 'Emergency response times are slow in remote highway sections. Accidents often require assessment before emergency services can respond effectively.',
                'proposed_solution' => 'Deploy strategically placed drone stations along major highways. Drones equipped with cameras, first-aid supplies, and communication devices can reach accident sites within minutes to assess damage and provide initial support.',
                'cost_benefit_analysis' => 'Initial investment: KSh 300M. Expected benefits: 50% faster emergency response, potential to save lives through rapid medical intervention, reduced secondary accidents.',
                'declaration_of_interests' => 'No conflicts of interest. This proposal draws from successful implementations in other countries.',
                'original_idea_disclaimer' => true,
                'collaboration_enabled' => true,
                'team_effort' => false,
                'team_members' => null,
                'status' => 'submitted',
            ],
            [
                'idea_title' => 'Smart Highway Sensors Network',
                'thematic_area_id' => $thematicAreas->where('slug', 'data-analytics')->first()?->id ?? $thematicAreas->first()->id,
                'abstract' => 'Install comprehensive sensor networks to monitor road conditions, traffic patterns, and environmental factors in real-time.',
                'problem_statement' => 'Lack of real-time data about road conditions, traffic patterns, and environmental factors hinders effective maintenance and planning.',
                'proposed_solution' => 'Deploy IoT sensors that measure road temperature, moisture levels, traffic density, vehicle speeds, and air quality. Data will be collected and analyzed to predict maintenance needs and optimize traffic management.',
                'cost_benefit_analysis' => 'Network cost: KSh 800M. Benefits: 40% reduction in unexpected breakdowns, improved maintenance planning, data-driven decision making.',
                'declaration_of_interests' => 'No conflicts of interest. Standard infrastructure monitoring practice.',
                'original_idea_disclaimer' => true,
                'collaboration_enabled' => false,
                'team_effort' => false,
                'team_members' => null,
                'status' => 'draft',
            ],
            [
                'idea_title' => 'Idea Title Placeholder',
                'thematic_area_id' => $thematicAreas->random()->id,
                'abstract' => 'Abstract placeholder.',
                'problem_statement' => 'Problem statement placeholder.',
                'proposed_solution' => 'Proposed solution placeholder.',
                'cost_benefit_analysis' => 'Cost-benefit analysis placeholder.',
                'declaration_of_interests' => 'Declaration of interests placeholder.',
                'original_idea_disclaimer' => true,
                'collaboration_enabled' => false,
                'team_effort' => false,
                'team_members' => null,
                'status' => 'draft',
            ],
            [
                'idea_title' => 'Idea Title Placeholder',
                'thematic_area_id' => $thematicAreas->random()->id,
                'abstract' => 'Abstract placeholder.',
                'problem_statement' => 'Problem statement placeholder.',
                'proposed_solution' => 'Proposed solution placeholder.',
                'cost_benefit_analysis' => 'Cost-benefit analysis placeholder.',
                'declaration_of_interests' => 'Declaration of interests placeholder.',
                'original_idea_disclaimer' => true,
                'collaboration_enabled' => false,
                'team_effort' => false,
                'team_members' => null,
                'status' => 'draft',
            ],
            [
                'idea_title' => 'Idea Title Placeholder',
                'thematic_area_id' => $thematicAreas->random()->id,
                'abstract' => 'Abstract placeholder.',
                'problem_statement' => 'Problem statement placeholder.',
                'proposed_solution' => 'Proposed solution placeholder.',
                'cost_benefit_analysis' => 'Cost-benefit analysis placeholder.',
                'declaration_of_interests' => 'Declaration of interests placeholder.',
                'original_idea_disclaimer' => true,
                'collaboration_enabled' => false,
                'team_effort' => false,
                'team_members' => null,
                'status' => 'draft',
            ],
            [
                'idea_title' => 'Idea Title Placeholder',
                'thematic_area_id' => $thematicAreas->random()->id,
                'abstract' => 'Abstract placeholder.',
                'problem_statement' => 'Problem statement placeholder.',
                'proposed_solution' => 'Proposed solution placeholder.',
                'cost_benefit_analysis' => 'Cost-benefit analysis placeholder.',
                'declaration_of_interests' => 'Declaration of interests placeholder.',
                'original_idea_disclaimer' => true,
                'collaboration_enabled' => false,
                'team_effort' => false,
                'team_members' => null,
                'status' => 'draft',
            ],
            [
                'idea_title' => 'Idea Title Placeholder',
                'thematic_area_id' => $thematicAreas->random()->id,
                'abstract' => 'Abstract placeholder.',
                'problem_statement' => 'Problem statement placeholder.',
                'proposed_solution' => 'Proposed solution placeholder.',
                'cost_benefit_analysis' => 'Cost-benefit analysis placeholder.',
                'declaration_of_interests' => 'Declaration of interests placeholder.',
                'original_idea_disclaimer' => true,
                'collaboration_enabled' => false,
                'team_effort' => false,
                'team_members' => null,
                'status' => 'draft',
            ],
            [
                'idea_title' => 'Idea Title Placeholder',
                'thematic_area_id' => $thematicAreas->random()->id,
                'abstract' => 'Abstract placeholder.',
                'problem_statement' => 'Problem statement placeholder.',
                'proposed_solution' => 'Proposed solution placeholder.',
                'cost_benefit_analysis' => 'Cost-benefit analysis placeholder.',
                'declaration_of_interests' => 'Declaration of interests placeholder.',
                'original_idea_disclaimer' => true,
                'collaboration_enabled' => false,
                'team_effort' => false,
                'team_members' => null,
                'status' => 'draft',
            ],
            [
                'idea_title' => 'Idea Title Placeholder',
                'thematic_area_id' => $thematicAreas->random()->id,
                'abstract' => 'Abstract placeholder.',
                'problem_statement' => 'Problem statement placeholder.',
                'proposed_solution' => 'Proposed solution placeholder.',
                'cost_benefit_analysis' => 'Cost-benefit analysis placeholder.',
                'declaration_of_interests' => 'Declaration of interests placeholder.',
                'original_idea_disclaimer' => true,
                'collaboration_enabled' => false,
                'team_effort' => false,
                'team_members' => null,
                'status' => 'draft',
            ],
            [
                'idea_title' => 'Idea Title Placeholder',
                'thematic_area_id' => $thematicAreas->random()->id,
                'abstract' => 'Abstract placeholder.',
                'problem_statement' => 'Problem statement placeholder.',
                'proposed_solution' => 'Proposed solution placeholder.',
                'cost_benefit_analysis' => 'Cost-benefit analysis placeholder.',
                'declaration_of_interests' => 'Declaration of interests placeholder.',
                'original_idea_disclaimer' => true,
                'collaboration_enabled' => false,
                'team_effort' => false,
                'team_members' => null,
                'status' => 'draft',
            ],
            [
                'idea_title' => 'Idea Title Placeholder',
                'thematic_area_id' => $thematicAreas->random()->id,
                'abstract' => 'Abstract placeholder.',
                'problem_statement' => 'Problem statement placeholder.',
                'proposed_solution' => 'Proposed solution placeholder.',
                'cost_benefit_analysis' => 'Cost-benefit analysis placeholder.',
                'declaration_of_interests' => 'Declaration of interests placeholder.',
                'original_idea_disclaimer' => true,
                'collaboration_enabled' => false,
                'team_effort' => false,
                'team_members' => null,
                'status' => 'draft',
            ],
            [
                'idea_title' => 'Idea Title Placeholder',
                'thematic_area_id' => $thematicAreas->random()->id,
                'abstract' => 'Abstract placeholder.',
                'problem_statement' => 'Problem statement placeholder.',
                'proposed_solution' => 'Proposed solution placeholder.',
                'cost_benefit_analysis' => 'Cost-benefit analysis placeholder.',
                'declaration_of_interests' => 'Declaration of interests placeholder.',
                'original_idea_disclaimer' => true,
                'collaboration_enabled' => false,
                'team_effort' => false,
                'team_members' => null,
                'status' => 'draft',
            ],
            [
                'idea_title' => 'Idea Title Placeholder',
                'thematic_area_id' => $thematicAreas->random()->id,
                'abstract' => 'Abstract placeholder.',
                'problem_statement' => 'Problem statement placeholder.',
                'proposed_solution' => 'Proposed solution placeholder.',
                'cost_benefit_analysis' => 'Cost-benefit analysis placeholder.',
                'declaration_of_interests' => 'Declaration of interests placeholder.',
                'original_idea_disclaimer' => true,
                'collaboration_enabled' => false,
                'team_effort' => false,
                'team_members' => null,
                'status' => 'draft',
            ],
            [
                'idea_title' => 'Idea Title Placeholder',
                'thematic_area_id' => $thematicAreas->random()->id,
                'abstract' => 'Abstract placeholder.',
                'problem_statement' => 'Problem statement placeholder.',
                'proposed_solution' => 'Proposed solution placeholder.',
                'cost_benefit_analysis' => 'Cost-benefit analysis placeholder.',
                'declaration_of_interests' => 'Declaration of interests placeholder.',
                'original_idea_disclaimer' => true,
                'collaboration_enabled' => false,
                'team_effort' => false,
                'team_members' => null,
                'status' => 'draft',
            ],
            [
                'idea_title' => 'Idea Title Placeholder',
                'thematic_area_id' => $thematicAreas->random()->id,
                'abstract' => 'Abstract placeholder.',
                'problem_statement' => 'Problem statement placeholder.',
                'proposed_solution' => 'Proposed solution placeholder.',
                'cost_benefit_analysis' => 'Cost-benefit analysis placeholder.',
                'declaration_of_interests' => 'Declaration of interests placeholder.',
                'original_idea_disclaimer' => true,
                'collaboration_enabled' => false,
                'team_effort' => false,
                'team_members' => null,
                'status' => 'draft',
            ],
            [
                'idea_title' => 'Idea Title Placeholder',
                'thematic_area_id' => $thematicAreas->random()->id,
                'abstract' => 'Abstract placeholder.',
                'problem_statement' => 'Problem statement placeholder.',
                'proposed_solution' => 'Proposed solution placeholder.',
                'cost_benefit_analysis' => 'Cost-benefit analysis placeholder.',
                'declaration_of_interests' => 'Declaration of interests placeholder.',
                'original_idea_disclaimer' => true,
                'collaboration_enabled' => false,
                'team_effort' => false,
                'team_members' => null,
                'status' => 'draft',
            ],
            [
                'idea_title' => 'Idea Title Placeholder',
                'thematic_area_id' => $thematicAreas->random()->id,
                'abstract' => 'Abstract placeholder.',
                'problem_statement' => 'Problem statement placeholder.',
                'proposed_solution' => 'Proposed solution placeholder.',
                'cost_benefit_analysis' => 'Cost-benefit analysis placeholder.',
                'declaration_of_interests' => 'Declaration of interests placeholder.',
                'original_idea_disclaimer' => true,
                'collaboration_enabled' => false,
                'team_effort' => false,
                'team_members' => null,
                'status' => 'draft',
            ],
            [
                'idea_title' => 'Idea Title Placeholder',
                'thematic_area_id' => $thematicAreas->random()->id,
                'abstract' => 'Abstract placeholder.',
                'problem_statement' => 'Problem statement placeholder.',
                'proposed_solution' => 'Proposed solution placeholder.',
                'cost_benefit_analysis' => 'Cost-benefit analysis placeholder.',
                'declaration_of_interests' => 'Declaration of interests placeholder.',
                'original_idea_disclaimer' => true,
                'collaboration_enabled' => false,
                'team_effort' => false,
                'team_members' => null,
                'status' => 'draft',
            ],
            [
                'idea_title' => 'Idea Title Placeholder',
                'thematic_area_id' => $thematicAreas->random()->id,
                'abstract' => 'Abstract placeholder.',
                'problem_statement' => 'Problem statement placeholder.',
                'proposed_solution' => 'Proposed solution placeholder.',
                'cost_benefit_analysis' => 'Cost-benefit analysis placeholder.',
                'declaration_of_interests' => 'Declaration of interests placeholder.',
                'original_idea_disclaimer' => true,
                'collaboration_enabled' => false,
                'team_effort' => false,
                'team_members' => null,
                'status' => 'draft',
            ],
            [
                'idea_title' => 'Idea Title Placeholder',
                'thematic_area_id' => $thematicAreas->random()->id,
                'abstract' => 'Abstract placeholder.',
                'problem_statement' => 'Problem statement placeholder.',
                'proposed_solution' => 'Proposed solution placeholder.',
                'cost_benefit_analysis' => 'Cost-benefit analysis placeholder.',
                'declaration_of_interests' => 'Declaration of interests placeholder.',
                'original_idea_disclaimer' => true,
                'collaboration_enabled' => false,
                'team_effort' => false,
                'team_members' => null,
                'status' => 'draft',
            ],
            [
                'idea_title' => 'Idea Title Placeholder',
                'thematic_area_id' => $thematicAreas->random()->id,
                'abstract' => 'Abstract placeholder.',
                'problem_statement' => 'Problem statement placeholder.',
                'proposed_solution' => 'Proposed solution placeholder.',
                'cost_benefit_analysis' => 'Cost-benefit analysis placeholder.',
                'declaration_of_interests' => 'Declaration of interests placeholder.',
                'original_idea_disclaimer' => true,
                'collaboration_enabled' => false,
                'team_effort' => false,
                'team_members' => null,
                'status' => 'draft',
            ],
            [
                'idea_title' => 'Idea Title Placeholder',
                'thematic_area_id' => $thematicAreas->random()->id,
                'abstract' => 'Abstract placeholder.',
                'problem_statement' => 'Problem statement placeholder.',
                'proposed_solution' => 'Proposed solution placeholder.',
                'cost_benefit_analysis' => 'Cost-benefit analysis placeholder.',
                'declaration_of_interests' => 'Declaration of interests placeholder.',
                'original_idea_disclaimer' => true,
                'collaboration_enabled' => false,
                'team_effort' => false,
                'team_members' => null,
                'status' => 'draft',
            ]
        ];

        $createdIdeas = [];

        foreach ($ideas as $ideaData) {
            $idea = Idea::create([
                'idea_title' => $ideaData['idea_title'],
                'slug' => Str::slug($ideaData['idea_title']) . '-' . Str::random(6),
                'thematic_area_id' => $ideaData['thematic_area_id'],
                'abstract' => $ideaData['abstract'],
                'problem_statement' => $ideaData['problem_statement'],
                'proposed_solution' => $ideaData['proposed_solution'],
                'cost_benefit_analysis' => $ideaData['cost_benefit_analysis'],
                'declaration_of_interests' => $ideaData['declaration_of_interests'],
                'original_idea_disclaimer' => $ideaData['original_idea_disclaimer'],
                'collaboration_enabled' => $ideaData['collaboration_enabled'],
                'team_effort' => $ideaData['team_effort'],
                'team_members' => $ideaData['team_members'],
                'status' => $ideaData['status'],
                'user_id' => $users->random()->id,
            ]);

            $createdIdeas[] = $idea;
        }

        // Create comments for the submitted ideas
        $submittedIdeas = collect($createdIdeas)->where('status', 'submitted');

        $comments = [
            [
                'content' => 'This is a fantastic idea! The AI-powered traffic management could revolutionize how we handle congestion in Nairobi. Have you considered integrating with existing traffic cameras?',
                'replies' => [
                    'Thanks for the feedback! Yes, we\'ve planned for integration with existing CCTV infrastructure. The system will use both dedicated AI cameras and existing security cameras.',
                    'Great to hear! This could also help with emergency response by automatically detecting accidents.',
                ],
            ],
            [
                'content' => 'The solar lighting proposal addresses a critical need. The cost-benefit analysis looks solid. How do you plan to handle maintenance of the solar panels in remote areas?',
                'replies' => [
                    'Excellent question! We\'ll implement a predictive maintenance system using IoT sensors to monitor panel performance and schedule maintenance before failures occur.',
                ],
            ],
            [
                'content' => 'The mobile app idea is practical and cost-effective. This could significantly improve our maintenance response times. Will it include offline functionality for areas with poor network coverage?',
                'replies' => [
                    'Absolutely! The app will have offline caching capabilities and sync data when connectivity is restored. We\'re also considering satellite communication for truly remote areas.',
                    'This is crucial for our field teams. The current paper-based system is inefficient and prone to data loss.',
                ],
            ],
            [
                'content' => 'The drone network proposal is innovative and could save lives. Have you considered the regulatory aspects of drone operations in Kenya?',
                'replies' => [
                    'We\'ve consulted with KCAA (Kenya Civil Aviation Authority) and have their preliminary approval. The drones will operate under controlled conditions with geofencing.',
                    'This could also be used for highway patrols and monitoring illegal activities.',
                ],
            ],
        ];

        foreach ($submittedIdeas as $idea) {
            // Create 1-3 comments per submitted idea
            $numComments = rand(1, 3);
            $selectedComments = collect($comments)->random($numComments);

            foreach ($selectedComments as $commentData) {
                $comment = Comment::create([
                    'user_id' => $users->random()->id,
                    'idea_id' => $idea->id,
                    'content' => $commentData['content'],
                    'parent_id' => null,
                ]);

                // Create replies if any
                if (isset($commentData['replies'])) {
                    foreach ($commentData['replies'] as $replyContent) {
                        Comment::create([
                            'user_id' => $users->random()->id,
                            'idea_id' => $idea->id,
                            'content' => $replyContent,
                            'parent_id' => $comment->id,
                        ]);
                    }
                }
            }
        }

        $this->command->info('Created ' . count($createdIdeas) . ' ideas and associated comments.');
    }
}