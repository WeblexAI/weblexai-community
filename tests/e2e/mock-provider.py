import json
import re
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

REQUEST_COUNT = 0


def extract_items(messages):
    prompt = "\n".join(str(message.get("content", "")) for message in messages)
    match = re.search(r"Items:\s*(\[.*?\])\s*(?:Return|$)", prompt, re.DOTALL)
    if not match:
        return [{"id": "fallback", "text": "Hello"}]

    try:
        items = json.loads(match.group(1))
    except json.JSONDecodeError:
        return [{"id": "fallback", "text": "Hello"}]

    return items if isinstance(items, list) and items else [{"id": "fallback", "text": "Hello"}]


class Handler(BaseHTTPRequestHandler):
    def do_GET(self):
        if self.path == "/health":
            return self.respond(200, {"status": "ok"})

        if self.path == "/stats":
            return self.respond(200, {"requests": REQUEST_COUNT})

        return self.respond(404, {"message": "Not found."})

    def do_POST(self):
        global REQUEST_COUNT

        if self.path != "/v1/chat/completions":
            return self.respond(404, {"message": "Not found."})

        length = int(self.headers.get("Content-Length", "0"))
        body = self.rfile.read(length)

        try:
            payload = json.loads(body)
        except json.JSONDecodeError:
            return self.respond(400, {"message": "Invalid JSON."})

        REQUEST_COUNT += 1
        items = extract_items(payload.get("messages", []))
        translations = [
            {"translated": f"Mock FR: {str(item.get('text', '')).strip()}"}
            for item in items
        ]

        return self.respond(
            200,
            {
                "id": "chatcmpl-e2e",
                "object": "chat.completion",
                "choices": [
                    {
                        "index": 0,
                        "message": {
                            "role": "assistant",
                            "content": json.dumps({"translations": translations}),
                        },
                        "finish_reason": "stop",
                    }
                ],
            },
        )

    def log_message(self, *_):
        return

    def respond(self, status, payload):
        data = json.dumps(payload).encode()
        self.send_response(status)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(data)))
        self.end_headers()
        self.wfile.write(data)


ThreadingHTTPServer(("0.0.0.0", 8081), Handler).serve_forever()
