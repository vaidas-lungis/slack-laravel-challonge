<?php
namespace App\SlashCommands;

use App\Participant;
use Spatie\SlashCommand\Attachment;
use Spatie\SlashCommand\AttachmentField;
use Spatie\SlashCommand\Handlers\BaseHandler;
use Spatie\SlashCommand\Request;
use Spatie\SlashCommand\Response;

class FindPendingPlayersGame extends BaseHandler
{

    protected $signature = '* find game : The command you want information about}';

    protected $description = 'List games between provided participants';


    /**
     * If this function returns true, the handle method will get called.
     *
     * @param \Spatie\SlashCommand\Request $request
     *
     * @return bool
     */
    public function canHandle(Request $request): bool
    {
        return starts_with($request->text, 'find game');
    }

    public function getParticipant($searchTerm)
    {
        if (!isset($searchTerm)){
            return 'Missing participant name';
        }

        $participantSearch = trim($searchTerm);
        $result = \App\Participant::where('name', 'like', '%'.$participantSearch.'%')->take(2)->get();

        if (count($result) > 1){
            return 'Participant name `'.$participantSearch.'` too ambiguous.';
        }

        if (count($result) === 0){
            return 'Could not find any player by name `'.$participantSearch.'`';
        }

        return array_first($result);
    }

    public function handle(Request $request): Response
    {
        $matches = null;
        preg_match('/(find game) (\w+) *(.+)?$/', $request->text, $matches);

        $result = $this->getParticipant($matches[2]);
        if (!$result instanceof Participant){
            return $this->respondToSlack($result)
                ->withAttachment(Attachment::create()
                    ->setColor('danger'));
        }
        $firstParticipant = $result;

        $result = $this->getParticipant($matches[3]);
        if (!$result instanceof Participant){
            return $this->respondToSlack($result)
                ->withAttachment(Attachment::create()
                    ->setColor('danger'));
        }
        $secondParticipant = $result;

        $query = http_build_query([
            'participant_id' => $firstParticipant->challonge_id,
        ]);
        $url = 'tournaments/'.env('CHALLONGE_TOURNAMENT').'/matches.json'.'?'.$query;
        $response = app('ChallongeHttpClient')->get( $url);
        $games = collect(json_decode($response->getBody()->getContents(), 1));

        $filteredGames = $games->filter(function($item) use ($secondParticipant){
            if ($item['match']['player2_id'] == $secondParticipant->challonge_id){
                return true;
            }

            if ($item['match']['player1_id'] == $secondParticipant->challonge_id){
                return true;
            }
        });

        if(empty($filteredGames)){
            return $this->respondToSlack('No games found between '.$firstParticipant->name.' and '.$secondParticipant->name);
        }

        $responseAttachments = [];
        foreach ($filteredGames as $game){
            $score = ['-', '-'];
            $color = '#36a64f';
            if ($game['match']['state'] == 'complete'){
                $score = explode('-',$game['match']['scores_csv']);
                $color = '#4169E1';
            }

            $player1 = $game['match']['player1_id'] == $firstParticipant->challonge_id ? $firstParticipant->name : $secondParticipant->name;
            $player2 = $game['match']['player2_id'] == $secondParticipant->challonge_id ? $secondParticipant->name : $firstParticipant->name;
            $responseAttachments[] = Attachment::create()
                ->setText("Round ".$game['match']['round']." Game ".$game['match']['suggested_play_order'])
                ->setColor($color)
                ->setFields([
                    AttachmentField::create($player1, $score[0])->displaySideBySide(),
                    AttachmentField::create($player2, $score[1])->displaySideBySide()
                ]);
        }

        return $this->respondToSlack($firstParticipant->name.' vs '.$secondParticipant->name)
            ->withAttachments($responseAttachments);

    }
}
