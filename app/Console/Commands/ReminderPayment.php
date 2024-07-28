<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReminderInvoice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminder:invoice';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reminder invoice for tenant';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Log::info('ReminderPayment command started.');

        try {
            $tenants = Tenant::all();
            Log::info('Fetched tenants: ' . $tenants->count());

            foreach ($tenants as $tenant) {
                $leaseStartDate = Carbon::parse($tenant->lease_start_date);
                $rentReminderDate = Carbon::parse($tenant->rent_reminder_date); 
                
                // Extract the day from lease_start_date and rent_reminder_date
                $leaseStartDay = $leaseStartDate->day;
                $rentReminderDay = $rentReminderDate->day;

                // Calculate the difference in days
                $diffDays = abs($leaseStartDay - $rentReminderDay);

                Log::info('Processing tenant ID: ' . $tenant->id);
                Log::info('Lease Start Day: ' . $leaseStartDay);
                Log::info('Rent Reminder Day: ' . $rentReminderDay);
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
                        "message" => "You have less than 10 days to pay the monthly payment. Lease Start Date Day: {$leaseStartDay}, Rent Reminder Day: {$rentReminderDay}"
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
