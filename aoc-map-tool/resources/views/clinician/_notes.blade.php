<section class="notes-section">
    <ul>
        @foreach($notes as $note)
        <li>
            <div class="notes">
                <p>{{$note->title}}</p>
            </div>
        </li>
        @endforeach
    </ul>
</section>
