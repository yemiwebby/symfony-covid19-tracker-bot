<?php

namespace App\Controller;

use Carbon\Carbon;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Twilio\Rest\Client;

class CovidBotController extends AbstractController
{
    /**
     * @Route("/api/covid", name="covid_bot")
     */
    public function countryCasesSummary(Request $request)
    {
        $from = $request->request->get('From');
        $body = $request->request->get('Body');

        $httpClient = HttpClient::create();
        $response = $httpClient->request("GET", "https://covid19.mathdro.id/api/countries/$body");

        if ($response->getStatusCode() === 200) {
            $trackerResult = json_decode($response->getContent());

            $confirmed = $trackerResult->confirmed->value;
            $recovered = $trackerResult->recovered->value;
            $deaths = $trackerResult->deaths->value;
            $lastUpdate = Carbon::parse($trackerResult->lastUpdate)->diffForHumans();

            $message = "Here is the summary of the COVID-19 cases in " . '*'.$body.'*' . " as at " . $lastUpdate . "\n\n";
            $message .= "*Confirmed Cases:* $confirmed \n";
            $message .= "*Recovered Cases:* $recovered \n";
            $message .= "*Deaths Recorded:* $deaths \n";
            $message .= "*lastUpdate:* $lastUpdate \n";
            $this->postMessageToWhatsApp($message, $from);
            return new JsonResponse([
                'success' => true,
            ]);
        } else {
            $this->postMessageToWhatsApp("Country *$body* not found or doesn't have any cases", $from);
            return new JsonResponse([
                'success' => false,
            ]);
        }
    }


    public function postMessageToWhatsApp(string $message, string $recipient)
    {
        $twilio_whatsapp_number = getenv('TWILIO_WHATSAPP_NUMBER');
        $account_sid = getenv("TWILIO_SID");
        $auth_token = getenv("TWILIO_AUTH_TOKEN");

        $client = new Client($account_sid, $auth_token);
        return $client->messages->create($recipient, array('from' => "whatsapp:$twilio_whatsapp_number", 'body' => $message));
    }
}