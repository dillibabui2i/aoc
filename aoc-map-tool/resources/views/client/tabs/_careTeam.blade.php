<section class="care-team-section">
    <ul>
        @foreach($careTeam as $user)
        <li>
            @if (file_exists('storage/user_profile/'.$user['profilePic']) && $user['profilePic'] != '')
            <img src="{{ env('APP_URL').'/storage/user_profile/'.$user['profilePic'] }}"  alt=""/>
            @else
            <img src="{{ env('APP_URL').'/storage/user_profile/user_default.png'}}" alt="" width="60" />
            @endif
            <div class="profile-details">
                <p>{{$user['name']}}</p>
                <p>{{$user['designation']}}</p>
            </div>
        </li>
        @endforeach
    </ul>
</section>
