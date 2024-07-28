<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Exception;

class SendTestSms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:test-sms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test SMS to tenants with lease start dates less than 10 days from the due date';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Log::info('SendTestSms command started.');

        try {
            $tenants = Tenant::all();
            Log::info('Fetched tenants: ' . $tenants->count());

            foreach ($tenants as $tenant) {
                $leaseStartDate = Carbon::parse($tenant->lease_start_date);
                $dueDay = $tenant->due_date; // Assuming due_date is an integer representing just the day

                // Extract the day from lease_start_date
                $leaseStartDay = $leaseStartDate->day;

                // Calculate the difference in days
                // Since due_date is only the day, you might want to compare this differently
                $diffDays = abs($leaseStartDay - $dueDay);

                Log::info('Processing tenant ID: ' . $tenant->id);
                Log::info('Lease Start Day: ' . $leaseStartDay);
                Log::info('Due Day: ' . $dueDay);
                Log::info('Diff Days: ' . $diffDays);

                if ($diffDays < 10) {
                    $user = User::find($tenant->user_id);

                    if (!$user || !$user->contact_number) {
                        Log::error('User or contact number not found for tenant ID ' . $tenant->id);
                        continue;
                    }

                    $message = [
                        "secret" => env('SMS_API_SECRET'),
                        "mode" => "devices",
                        "device" => env('SMS_API_DEVICE_ID'),
                        "sim" => 1,
                        "priority" => 1,
                        "phone" => $user->contact_number,
                        "message" => "You have less than 10 days to pay the monthly payment. Lease Start Date Day: {$leaseStartDay}, Due Date Day: {$dueDay}"
                    ];

                    $cURL = curl_init("https://sms.olisd.com/api/send/sms");
                    curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($cURL, CURLOPT_POSTFIELDS, $message);
                    $response = curl_exec($cURL);
                    curl_close($cURL);

                    $result = json_decode($response, true);

                    if ($result['status'] == 200) {
                        Log::info('SMS sent successfully to ' . $user->contact_number);
                        $this->info('SMS sent successfully to ' . $user->contact_number);
                    } else {
                        Log::error('Failed to send SMS: ' . $result['message']);
                        $this->error('Failed to send SMS: ' . $result['message']);
                    }
                }
            }
        } catch (Exception $e) {
            Log::error('Error sending SMS: ' . $e->getMessage());
            $this->error('Error sending SMS: ' . $e->getMessage());
        }

        Log::info('SendTestSms command completed.');
        return 0;
    }
}
