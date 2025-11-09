# Events & Jobs

Decouple your application with events and process background tasks with jobs.

## Events

Events provide a simple observer pattern implementation for decoupling application components.

### Creating Events

Generate event:

```bash
php lalaz g:event UserRegistered
```

Creates `app/Events/UserRegistered.php`:

```php
<?php

namespace App\Events;

use Lalaz\Event\EventHub;

class UserRegistered extends EventHub
{
    public function __construct(
        public readonly int $userId,
        public readonly string $email
    ) {}
}
```

### Dispatching Events

```php
use App\Events\UserRegistered;

public function register(Request $request)
{
    $user = User::create($data);
    
    // Dispatch event
    event(UserRegistered::class, [
        'userId' => $user->id,
        'email' => $user->email
    ]);
    
    return Response::redirect('/dashboard');
}
```

### Listening to Events

Create listener:

```bash
php lalaz g:listener SendWelcomeEmail
```

Register in `bootstrap/events.php`:

```php
<?php

use App\Events\UserRegistered;
use App\Listeners\SendWelcomeEmail;
use App\Listeners\CreateUserProfile;
use App\Listeners\LogUserActivity;

return [
    UserRegistered::class => [
        SendWelcomeEmail::class,
        CreateUserProfile::class,
        LogUserActivity::class
    ]
];
```

Implement listener:

```php
<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use Lalaz\Event\EventListener;

class SendWelcomeEmail extends EventListener
{
    public function handle(UserRegistered $event): void
    {
        // Send welcome email
        mail(
            $event->email,
            'Welcome!',
            "Welcome to our app, user {$event->userId}!"
        );
    }
}
```

## Jobs (Queue System)

Process time-consuming tasks in the background.

### Creating Jobs

Generate job:

```bash
php lalaz g:job SendEmailJob
```

Creates `app/Jobs/SendEmailJob.php`:

```php
<?php

namespace App\Jobs;

use Lalaz\Queue\Job;

class SendEmailJob extends Job
{
    public function __construct(
        private string $email,
        private string $subject,
        private string $body
    ) {}
    
    public function handle(): void
    {
        mail($this->email, $this->subject, $this->body);
    }
}
```

### Dispatching Jobs

```php
use App\Jobs\SendEmailJob;

public function sendNotification(Request $request)
{
    // Queue job for background processing
    job(SendEmailJob::class, [
        'email' => $request->input('email'),
        'subject' => 'Notification',
        'body' => 'You have a new notification'
    ]);
    
    return Response::json(['message' => 'Email queued']);
}
```

### Running Jobs

#### Process Once

```bash
php lalaz jobs:once
```

Processes all pending jobs once and exits.

#### Run Worker (Daemon)

```bash
php lalaz jobs:run
```

Runs continuously, processing jobs as they arrive.

### Job Configuration

In `.env`:

```env
QUEUE_DRIVER=database
QUEUE_TABLE=jobs
QUEUE_FAILED_TABLE=failed_jobs
```

### Job with Delay

```php
job(SendEmailJob::class, [
    'email' => $user->email,
    'subject' => 'Welcome!',
    'body' => 'Thanks for signing up!'
], delay: 60); // Delay 60 seconds
```

### Job Priority

```php
// High priority (processed first)
job(UrgentNotificationJob::class, [...], priority: 1);

// Normal priority
job(SendEmailJob::class, [...], priority: 5);

// Low priority
job(CleanupJob::class, [...], priority: 10);
```

## Complete Example

### Event + Listener + Job

**Event**:

```php
<?php

namespace App\Events;

use Lalaz\Event\EventHub;

class OrderPlaced extends EventHub
{
    public function __construct(
        public readonly int $orderId,
        public readonly int $userId,
        public readonly float $total
    ) {}
}
```

**Listeners**:

```php
<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Jobs\SendOrderConfirmationJob;
use App\Jobs\UpdateInventoryJob;
use Lalaz\Event\EventListener;

class ProcessOrderListeners extends EventListener
{
    public function handle(OrderPlaced $event): void
    {
        // Queue jobs for async processing
        job(SendOrderConfirmationJob::class, [
            'orderId' => $event->orderId,
            'userId' => $event->userId
        ]);
        
        job(UpdateInventoryJob::class, [
            'orderId' => $event->orderId
        ]);
    }
}
```

**Jobs**:

```php
<?php

namespace App\Jobs;

use Lalaz\Queue\Job;
use App\Models\Order;
use App\Models\User;

class SendOrderConfirmationJob extends Job
{
    public function __construct(
        private int $orderId,
        private int $userId
    ) {}
    
    public function handle(): void
    {
        $order = Order::find($this->orderId);
        $user = User::find($this->userId);
        
        // Send confirmation email
        mail(
            $user->email,
            'Order Confirmation',
            "Your order #{$order->id} has been placed successfully!"
        );
    }
}
```

**Controller**:

```php
public function placeOrder(Request $request)
{
    $order = Order::create([
        'user_id' => $request->session->get('user_id'),
        'total' => $cart->getTotal()
    ]);
    
    // Dispatch event
    event(OrderPlaced::class, [
        'orderId' => $order->id,
        'userId' => $order->user_id,
        'total' => $order->total
    ]);
    
    return Response::json($order, 201);
}
```

## Event Jobs

Create a job that's automatically dispatched by an event:

```bash
php lalaz g:eventjob SendWelcomeEmailJob
```

```php
<?php

namespace App\Jobs;

use Lalaz\Event\EventJob;
use App\Events\UserRegistered;

class SendWelcomeEmailJob extends EventJob
{
    public static function getEventClass(): string
    {
        return UserRegistered::class;
    }
    
    public function handle(UserRegistered $event): void
    {
        mail(
            $event->email,
            'Welcome!',
            "Welcome, user #{$event->userId}!"
        );
    }
}
```

Register in `bootstrap/events.php`:

```php
use App\Events\UserRegistered;
use App\Jobs\SendWelcomeEmailJob;

return [
    UserRegistered::class => [
        SendWelcomeEmailJob::class
    ]
];
```

## Best Practices

### 1. Use Events for Decoupling

```php
// ✅ Good - decoupled with events
public function register(Request $request)
{
    $user = User::create($data);
    event(UserRegistered::class, ['userId' => $user->id]);
    return Response::redirect('/dashboard');
}

// ❌ Bad - tightly coupled
public function register(Request $request)
{
    $user = User::create($data);
    $this->sendWelcomeEmail($user);
    $this->createProfile($user);
    $this->logActivity($user);
    return Response::redirect('/dashboard');
}
```

### 2. Use Jobs for Heavy Tasks

```php
// ✅ Good - async job
job(GenerateReportJob::class, ['userId' => $user->id]);
return Response::json(['message' => 'Report generation started']);

// ❌ Bad - blocks request
$report = $this->generateReport($user); // Takes 30 seconds
return Response::json($report);
```

### 3. Handle Job Failures

```php
class SendEmailJob extends Job
{
    public function handle(): void
    {
        try {
            mail($this->email, $this->subject, $this->body);
        } catch (Exception $e) {
            Log::error('Email sending failed', [
                'email' => $this->email,
                'error' => $e->getMessage()
            ]);
            
            throw $e; // Mark job as failed
        }
    }
}
```

### 4. Use Priority Wisely

```php
// ✅ Good - appropriate priorities
job(SendPasswordResetJob::class, [...], priority: 1);    // High
job(SendNewsletterJob::class, [...], priority: 5);       // Normal
job(CleanupTempFilesJob::class, [...], priority: 10);    // Low

// ❌ Bad - everything high priority
job(CleanupJob::class, [...], priority: 1);
```

### 5. Keep Event Data Simple

```php
// ✅ Good - simple data
class UserRegistered extends EventHub
{
    public function __construct(
        public readonly int $userId,
        public readonly string $email
    ) {}
}

// ❌ Bad - complex objects
class UserRegistered extends EventHub
{
    public function __construct(
        public readonly User $user,          // Full model
        public readonly array $allSettings   // Large array
    ) {}
}
```

## Monitoring Jobs

### View Queue Status

```php
// In a controller
$queueManager = app('queue');
$pending = $queueManager->count();

return Response::json([
    'pending_jobs' => $pending
]);
```

### Failed Jobs

Failed jobs are logged to `storage/logs/`:

```bash
grep "Job failed" storage/logs/*.log
```

## Supervisor Configuration

For production, use Supervisor to keep worker running:

```ini
[program:lalaz-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/app/lalaz jobs:run
autostart=true
autorestart=true
user=www-data
numprocs=3
redirect_stderr=true
stdout_logfile=/path/to/app/storage/logs/worker.log
```

Start worker:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start lalaz-worker:*
```

---

**Next**: [CLI Commands Reference](12-cli-commands.md) →
