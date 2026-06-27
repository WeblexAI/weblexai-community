import hashlib
import hmac
import json
import os
import re
import subprocess
import time
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

SECRET = os.environ.get("UPDATE_AGENT_SECRET", "").encode()
VERSION = re.compile(r"^\d+\.\d+\.\d+$")


class Handler(BaseHTTPRequestHandler):
    def do_POST(self):
        length = int(self.headers.get("Content-Length", "0"))
        body = self.rfile.read(length)
        timestamp = self.headers.get("X-Weblex-Timestamp", "")
        signature = self.headers.get("X-Weblex-Signature", "")

        if self.path != "/update" or len(SECRET) < 32:
            return self.respond(404)

        try:
            issued_at = int(timestamp)
        except ValueError:
            return self.respond(401)

        expected = hmac.new(SECRET, timestamp.encode() + b"." + body, hashlib.sha256).hexdigest()
        if abs(time.time() - issued_at) > 60 or not hmac.compare_digest(signature, expected):
            return self.respond(401)

        try:
            version = json.loads(body)["version"]
        except (KeyError, TypeError, json.JSONDecodeError):
            return self.respond(422)

        if not VERSION.fullmatch(version):
            return self.respond(422)

        subprocess.Popen(
            ["/usr/local/bin/update-weblex", version],
            cwd="/workspace",
            stdout=open("/backups/update.log", "a"),
            stderr=subprocess.STDOUT,
        )
        return self.respond(202, {"status": "accepted"})

    def log_message(self, *_):
        return

    def respond(self, status, payload=None):
        data = json.dumps(payload or {"message": "Request rejected."}).encode()
        self.send_response(status)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(data)))
        self.end_headers()
        self.wfile.write(data)


ThreadingHTTPServer(("0.0.0.0", 8080), Handler).serve_forever()
