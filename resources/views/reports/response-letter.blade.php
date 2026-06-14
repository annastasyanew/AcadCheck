<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Response to Reviewers</title>
    <style>
        @page {
            margin: 24px;
        }

        body {
            color: #111827;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            line-height: 1.4;
        }

        h1 {
            font-size: 18px;
            margin: 0 0 14px;
            text-align: center;
        }

        .meta {
            margin-bottom: 14px;
        }

        .meta p {
            margin: 2px 0;
        }

        table {
            border-collapse: collapse;
            table-layout: fixed;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #9ca3af;
            padding: 5px;
            vertical-align: top;
            word-wrap: break-word;
        }

        th {
            background-color: #e5e7eb;
            font-weight: bold;
            text-align: left;
        }

        .number {
            text-align: center;
            width: 3%;
        }

        .reviewer {
            width: 8%;
        }

        .comment {
            width: 20%;
        }

        .response {
            width: 25%;
        }

        .revision {
            width: 18%;
        }

        .location {
            width: 12%;
        }

        .status {
            width: 8%;
        }
    </style>
</head>
<body>
    <h1>Response to Reviewers</h1>

    <div class="meta">
        <p><strong>Article title:</strong> {{ $document->title }}</p>
        <p><strong>Generated at:</strong> {{ $generatedAt->format('d M Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="number">No.</th>
                <th class="reviewer">Reviewer</th>
                <th class="comment">Reviewer Comment</th>
                <th class="response">Author Response</th>
                <th class="revision">Revision Made</th>
                <th class="location">Revision Location</th>
                <th class="status">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($comments as $index => $comment)
                <tr>
                    <td class="number">{{ $index + 1 }}</td>
                    <td class="reviewer">
                        {{ $comment->reviewer_label }}
                        @if ($comment->comment_number)
                            <br>Comment {{ $comment->comment_number }}
                        @endif
                    </td>
                    <td class="comment">
                        {{ $comment->original_comment }}
                        @if ($comment->related_section)
                            <br><strong>Section:</strong> {{ $comment->related_section }}
                        @endif
                        <br><strong>Priority:</strong> {{ $comment->priority }}
                    </td>
                    <td class="response">{{ $comment->response?->author_response ?? '-' }}</td>
                    <td class="revision">{{ $comment->response?->revision_made ?? '-' }}</td>
                    <td class="location">{{ $comment->response?->revision_location ?? '-' }}</td>
                    <td class="status">{{ str_replace('_', ' ', $comment->status) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
