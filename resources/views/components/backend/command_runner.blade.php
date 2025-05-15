<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Run Artisan Command</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container my-5">
        <h1>Run Artisan Command</h1>
        
        <!-- Form to submit Artisan command -->
        <form action="{{ route('run.command') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="command" class="form-label">Enter Artisan Command</label>
                <input type="text" class="form-control" id="command" name="command" placeholder="e.g., migrate">
            </div>

            <!-- Buttons for common Artisan commands -->
            <div class="mb-3">
                <label class="form-label">Or Choose a Command</label><br>
                <!-- Miscellaneous commands -->
                <button type="button" class="btn btn-secondary" onclick="setCommand('optimize:clear')">optimize:clear</button>
                <button type="button" class="btn btn-secondary" onclick="setCommand('queue:work')">queue:work</button>
                <button type="button" class="btn btn-secondary" onclick="setCommand('queue:restart')">queue:restart</button>
                <button type="button" class="btn btn-secondary" onclick="setCommand('schedule:run')">schedule:run</button>
                <button type="button" class="btn btn-secondary" onclick="setCommand('event:cache')">event:cache</button>

                <!-- Migration-related commands -->
                <button type="button" class="btn btn-secondary" onclick="setCommand('migrate')">migrate</button>
                <button type="button" class="btn btn-secondary" onclick="setCommand('migrate:rollback')">migrate:rollback</button>
                <button type="button" class="btn btn-secondary" onclick="setCommand('migrate:fresh')">migrate:fresh</button>
                <button type="button" class="btn btn-secondary" onclick="setCommand('migrate:status')">migrate:status</button>
                
                <!-- Database-related commands -->
                <button type="button" class="btn btn-secondary" onclick="setCommand('db:seed')">db:seed</button>
                <button type="button" class="btn btn-secondary" onclick="setCommand('db:wipe')">db:wipe</button>
            
                <!-- Cache and config-related commands -->
                <button type="button" class="btn btn-secondary" onclick="setCommand('cache:clear')">cache:clear</button>
                <button type="button" class="btn btn-secondary" onclick="setCommand('config:clear')">config:clear</button>
                <button type="button" class="btn btn-secondary" onclick="setCommand('config:cache')">config:cache</button>
                <button type="button" class="btn btn-secondary" onclick="setCommand('view:clear')">view:clear</button>
                <button type="button" class="btn btn-secondary" onclick="setCommand('route:clear')">route:clear</button>
                
                <!-- Routes and views -->
                <button type="button" class="btn btn-secondary" onclick="setCommand('route:list')">route:list</button>
                <button type="button" class="btn btn-secondary" onclick="setCommand('view:cache')">view:cache</button>
            
                
            </div>
            
            <!-- Submit button -->
            <button type="submit" class="btn btn-primary">Run Command</button>
        </form>

        <!-- Display output if available -->
        @if(session('output'))
            <div class="mt-4">
                <h3>Command Output:</h3>
                <pre>{{ session('output') }}</pre>
            </div>
        @endif
    </div>

    <script>
        // Function to set the command input field with selected command
        function setCommand(command) {
            $('#command').val(command);
        }
    </script>
</body>
</html>
