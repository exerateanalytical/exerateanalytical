<?php

namespace Database\Seeders;

use App\Models\PetitionTemplate;
use Illuminate\Database\Seeder;

class PetitionTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'title'                  => 'Call for Transparent Public Spending',
                'description'            => 'Demand regular, itemised publication of all government expenditures above a defined threshold.',
                'category'               => 'policy',
                'summary_template'       => 'We, the undersigned, call on [AUTHORITY] to publish a full, itemised breakdown of all public spending above [THRESHOLD] on a [FREQUENCY] basis.',
                'body_template'          => "Citizens have a fundamental right to know how public money is spent.\n\n[AUTHORITY] currently does not provide adequate transparency around expenditure decisions. This lack of accountability erodes public trust and opens the door to misuse of funds.\n\nWe ask that:\n1. All contracts and payments above [THRESHOLD] be published within 30 days of approval.\n2. An independent audit be conducted annually.\n3. A searchable public database be made available at no cost.\n\nThis petition will be delivered to [AUTHORITY] upon reaching [GOAL] signatures.",
                'default_signature_goal' => 5000,
                'is_premium'             => false,
                'sort_order'             => 10,
            ],
            [
                'title'                  => 'Protect Local Green Spaces',
                'description'            => 'Oppose the conversion of parks and nature reserves for commercial or residential development.',
                'category'               => 'environment',
                'summary_template'       => 'We call on [AUTHORITY] to protect [LOCATION] from development and maintain it as green, publicly accessible space for future generations.',
                'body_template'          => "[LOCATION] is an irreplaceable green space that serves as the lungs of our community.\n\nThe proposed development at [LOCATION] would permanently destroy [AREA] of green space, displace wildlife, and deprive residents of [BENEFIT].\n\nWe urge [AUTHORITY] to:\n1. Reject the current development proposal.\n2. Reclassify [LOCATION] as a protected nature reserve.\n3. Commit to a community-led management plan.\n\nThe long-term value of green space — for health, biodiversity, and quality of life — far outweighs any short-term commercial gain.",
                'default_signature_goal' => 2500,
                'is_premium'             => false,
                'sort_order'             => 20,
            ],
            [
                'title'                  => 'Strengthen Whistleblower Protections',
                'description'            => 'Advocate for robust legal protections for individuals who report wrongdoing in public institutions.',
                'category'               => 'rights',
                'summary_template'       => 'We petition [AUTHORITY] to enact comprehensive whistleblower protection legislation that shields individuals who expose corruption or wrongdoing from retaliation.',
                'body_template'          => "Without whistleblowers, many of the most significant abuses of power would never come to light.\n\nCurrent protections under [EXISTING_LAW] are inadequate: they cover only [LIMITED_SCOPE], leave workers in [SECTOR] unprotected, and provide no meaningful redress against retaliation.\n\nWe call for:\n1. Extended coverage to all sectors, including private contractors working for government.\n2. An independent Whistleblower Protection Office with investigation powers.\n3. Guaranteed anonymity during preliminary investigations.\n4. Civil and criminal penalties for those who retaliate.\n\nProtecting those who speak up is essential to a functioning democracy.",
                'default_signature_goal' => 10000,
                'is_premium'             => true,
                'sort_order'             => 5,
            ],
            [
                'title'                  => 'Improve Public Transport Connectivity',
                'description'            => 'Call for expanded and more frequent public transport services in underserved communities.',
                'category'               => 'community',
                'summary_template'       => 'We urge [AUTHORITY] to invest in improving public transport links in [AREA] to reduce inequality and cut carbon emissions.',
                'body_template'          => "Residents of [AREA] are currently without adequate public transport options. The nearest [TRANSPORT_TYPE] stop is [DISTANCE] away, forcing residents to rely on private vehicles.\n\nThis creates a two-tier system that disadvantages those without cars — disproportionately affecting elderly residents, people with disabilities, young people, and low-income households.\n\nWe ask [AUTHORITY] to:\n1. Extend route [ROUTE] to serve [AREA] with at least [FREQUENCY] services per hour.\n2. Review pricing to ensure affordability for all income levels.\n3. Provide real-time journey information at all stops.\n\nConnected communities are healthier, greener, and more economically resilient.",
                'default_signature_goal' => 1000,
                'is_premium'             => false,
                'sort_order'             => 30,
            ],
            [
                'title'                  => 'Mandate Civic Education in Schools',
                'description'            => 'Ensure every student receives comprehensive education on democracy, rights, and civic participation.',
                'category'               => 'civic',
                'summary_template'       => 'We call on [AUTHORITY] to make evidence-based civic education a compulsory part of the national curriculum for all students aged [AGE_RANGE].',
                'body_template'          => "A healthy democracy depends on an informed and engaged citizenry. Yet civic education in our schools is fragmented, inconsistently delivered, and often absent entirely.\n\nStudies consistently show that students who receive structured civic education are:\n- More likely to vote as adults.\n- Better equipped to identify misinformation.\n- More engaged in voluntary and community activities.\n\nWe ask [AUTHORITY] to:\n1. Introduce a mandatory civic education module covering democracy, human rights, media literacy, and local government.\n2. Allocate at least [HOURS] per school year to civic learning.\n3. Train and resource teachers to deliver high-quality civic content.\n4. Evaluate outcomes annually and publish results.\n\nInvesting in civic knowledge today is investing in democratic resilience tomorrow.",
                'default_signature_goal' => 7500,
                'is_premium'             => true,
                'sort_order'             => 7,
            ],
        ];

        foreach ($templates as $data) {
            PetitionTemplate::firstOrCreate(
                ['title' => $data['title']],
                $data
            );
        }
    }
}
