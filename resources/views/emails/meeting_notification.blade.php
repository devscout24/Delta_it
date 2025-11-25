<p>Hello,</p>

<p>You have been invited to the meeting:</p>

<p><strong>Meeting Name:</strong> {{ $meeting->meeting_name }}</p>
<p><strong>Date:</strong> {{ $meeting->date }}</p>
<p><strong>Time:</strong> {{ $meeting->start_time }} - {{ $meeting->end_time }}</p>

@if($meeting->meeting_type == 'virtual')
    <p><strong>Online Link:</strong> <a href="{{ $meeting->online_link }}">{{ $meeting->online_link }}</a></p>
@else
    <p><strong>Location:</strong> {{ $meeting->location }}</p>
@endif

<p>Please be available on time.</p>

<p>Thank you.</p>
