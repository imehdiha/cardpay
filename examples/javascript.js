import crypto from "node:crypto";

const baseUrl = "https://pay.example.com";
const publicKey = "cp_pk_REPLACE_ME";
const secret = "cp_sk_REPLACE_ME";
const path = "/api/v1/payments";
const body = JSON.stringify({ amount: 80000000, external_order_id: "ORDER-123" });
const timestamp = Math.floor(Date.now() / 1000).toString();
const nonce = crypto.randomBytes(16).toString("hex");
const bodyHash = crypto.createHash("sha256").update(body).digest("hex");
const canonical = ["POST", path, "", bodyHash, timestamp, nonce].join("\n");
const signature = crypto.createHmac("sha256", secret).update(canonical).digest("hex");

const response = await fetch(baseUrl + path, { method: "POST", headers: { "Content-Type": "application/json", "X-CardPay-Key": publicKey, "X-CardPay-Timestamp": timestamp, "X-CardPay-Nonce": nonce, "X-CardPay-Signature": signature, "Idempotency-Key": "order-123-first-attempt" }, body });
console.log(await response.json());
