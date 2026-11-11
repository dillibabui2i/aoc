<form action="{{ route('snowflake-verifier.execute') }}" method="POST">
    @csrf
    <textarea name="query" rows="70" style="width: 100%"></textarea>
    <button type="submit">execute</button>
</form>
