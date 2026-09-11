import os
import time
import requests
from dotenv import load_dotenv
from playwright.sync_api import sync_playwright

load_dotenv()

API_URL = "https://yonggene.kolejsynergy.com/Yong_WhatsAppWeb/web/api"
WORKER_TOKEN = os.getenv("WORKER_TOKEN", "")

headers = {
    "Authorization": f"Bearer {WORKER_TOKEN}",
    "Content-Type": "application/json"
}

def send_heartbeat():
    try:
        requests.post(f"{API_URL}/worker/heartbeat.php", headers=headers, timeout=5)
    except Exception:
        pass

def poll_and_process():
    print("Starting WhatsApp Automation Worker...")
    
    with sync_playwright() as p:
        user_data_dir = os.path.abspath("browser_profile")
        browser = p.chromium.launch_persistent_context(
            user_data_dir=user_data_dir,
            headless=False,
            args=["--start-maximized"]
        )
        page = browser.pages[0] if browser.pages else browser.new_page()
        
        try:
            page.goto("https://web.whatsapp.com", timeout=60000)
        except Exception:
            pass
        
        print("Waiting for WhatsApp Web session to stabilize...")
        time.sleep(10)

        while True:
            try:
                send_heartbeat()
                
                res = requests.post(f"{API_URL}/jobs/claim.php", headers=headers, timeout=5)
                if res.status_code == 200:
                    try:
                        data = res.json()
                    except Exception:
                        time.sleep(5)
                        continue

                    if "job" in data and data["job"]:
                        job = data["job"]
                        job_id = job["id"]
                        phone = job["recipient_phone"]
                        body = job["message_body"]
                        
                        print(f"Claimed Job #{job_id} for {phone}")
                        
                        encoded_body = requests.utils.quote(body)
                        chat_url = f"https://web.whatsapp.com/send?phone={phone}&text={encoded_body}"
                        
                        try:
                            page.goto(chat_url, timeout=60000)
                            
                            send_button_selector = 'span[data-icon="send"]'
                            page.wait_for_selector(send_button_selector, timeout=15000)
                            time.sleep(1)
                            
                            page.locator(send_button_selector).click()
                            time.sleep(2)
                            
                            requests.post(f"{API_URL}/jobs/report.php", headers=headers, json={
                                "job_id": job_id,
                                "status": "sent"
                            }, timeout=5)
                            print(f"Job #{job_id} successfully sent and dispatched!")
                        
                        except Exception as ex:
                            try:
                                page.keyboard.press("Enter")
                                time.sleep(2)
                                requests.post(f"{API_URL}/jobs/report.php", headers=headers, json={
                                    "job_id": job_id,
                                    "status": "sent"
                                }, timeout=5)
                                print(f"Job #{job_id} sent via Enter key fallback!")
                            except Exception as inner_ex:
                                print(f"Job #{job_id} error: {inner_ex}")
                                requests.post(f"{API_URL}/jobs/report.php", headers=headers, json={
                                    "job_id": job_id,
                                    "status": "failed"
                                }, timeout=5)
                
            except Exception as e:
                print(f"Worker loop error: {e}")
            
            time.sleep(10)

if __name__ == "__main__":
    poll_and_process()