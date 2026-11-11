<section class="care-team-section">
    <ul>
        @foreach($careTeam as $user)
        <li>
            <img src="{{ env('APP_URL').'/storage/user_profile/'.$user['profilePic'] }}" />
            <div class="profile-details">
                <p>{{$user['name']}}</p>
                <p>{{$user['designation']}}</p>
            </div>
        </li>
        @endforeach
    </ul>
</section>
