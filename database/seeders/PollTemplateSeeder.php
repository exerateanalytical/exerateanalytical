<?php

namespace Database\Seeders;

use App\Models\PollTemplate;
use Illuminate\Database\Seeder;

class PollTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'title'       => 'General Approval Rating',
                'description' => 'Gauge public approval for a leader, institution, or policy with a simple five-point scale.',
                'category'    => 'general',
                'poll_type'   => 'standard',
                'options'     => ['Strongly approve', 'Approve', 'Neutral', 'Disapprove', 'Strongly disapprove'],
                'allow_multiple_votes' => false,
                'verified_only'        => false,
                'is_premium'           => false,
                'sort_order'           => 10,
            ],
            [
                'title'       => 'Policy Priority Ranking',
                'description' => 'Ask constituents to rank the most important policy areas for the next term. Best used as a ranked poll.',
                'category'    => 'policy',
                'poll_type'   => 'ranked',
                'options'     => ['Healthcare reform', 'Economic development', 'Education funding', 'Climate action', 'Public safety', 'Infrastructure'],
                'allow_multiple_votes' => false,
                'verified_only'        => false,
                'is_premium'           => false,
                'sort_order'           => 20,
            ],
            [
                'title'       => 'Election Candidate Preference',
                'description' => 'Collect anonymous preference data ahead of an election. Results inform campaign messaging and ground-game allocation.',
                'category'    => 'election',
                'poll_type'   => 'standard',
                'options'     => ['Candidate A', 'Candidate B', 'Candidate C', 'Undecided', 'Will not vote'],
                'allow_multiple_votes' => false,
                'verified_only'        => true,
                'is_premium'           => true,
                'sort_order'           => 5,
            ],
            [
                'title'       => 'Community Service Satisfaction',
                'description' => 'Measure resident satisfaction with local government services such as waste collection, parks, and public transport.',
                'category'    => 'community',
                'poll_type'   => 'standard',
                'options'     => ['Very satisfied', 'Satisfied', 'Somewhat satisfied', 'Dissatisfied', 'Very dissatisfied'],
                'allow_multiple_votes' => false,
                'verified_only'        => false,
                'is_premium'           => false,
                'sort_order'           => 30,
            ],
            [
                'title'       => 'Multi-Topic Civic Feedback',
                'description' => 'A flexible multi-select template allowing residents to choose all civic concerns they consider urgent.',
                'category'    => 'civic',
                'poll_type'   => 'standard',
                'options'     => ['Housing affordability', 'Public transport', 'Crime reduction', 'Green spaces', 'Job opportunities', 'School quality'],
                'allow_multiple_votes' => true,
                'verified_only'        => false,
                'is_premium'           => false,
                'sort_order'           => 40,
            ],
            [
                'title'       => 'Weighted Budget Allocation Simulation',
                'description' => 'Let constituents distribute a hypothetical budget across departments. Uses weighted scoring for nuanced results.',
                'category'    => 'policy',
                'poll_type'   => 'weighted',
                'options'     => ['Health & Social Care', 'Education', 'Infrastructure', 'Defence', 'Environment', 'Digital & Innovation'],
                'allow_multiple_votes' => true,
                'verified_only'        => true,
                'is_premium'           => true,
                'sort_order'           => 8,
            ],
        ];

        foreach ($templates as $data) {
            PollTemplate::firstOrCreate(
                ['title' => $data['title']],
                $data
            );
        }
    }
}
