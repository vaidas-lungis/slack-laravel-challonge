<?php

namespace App\Console\Commands\Participants;

use App\Participant;
use Illuminate\Console\Command;

class Init extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'participants:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch tournament participants and put it into the table';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $url = 'tournaments/'.env('CHALLONGE_TOURNAMENT').'/participants.json';
        $response = app('ChallongeHttpClient')->get( $url);
        $result = json_decode($response->getBody()->getContents(), 1);
        foreach($result as $item){
            Participant::create([
                'challonge_id' => $item['participant']['id'],
                'name' => $item['participant']['display_name']
            ]);
        }
    }
}
