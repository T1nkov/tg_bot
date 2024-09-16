import subprocess
import sys

def get_crontab():
    try:
        result = subprocess.run(['crontab', '-l'], capture_output=True, text=True, check=True)
        return result.stdout.splitlines()
    except subprocess.CalledProcessError as e:
        print(f"Cannot read crontab: {e.stderr.strip()}")
        sys.exit(1)

def update_crontab(cron_jobs):
    new_cron = "\n".join(cron_jobs)
    if new_cron and not new_cron.endswith('\n'):
        new_cron += '\n'
    process = subprocess.Popen(['crontab'], stdin=subprocess.PIPE, text=True)
    process.communicate(input=new_cron)
    process.wait()

def main():
    cron_jobs = get_crontab()
    if sys.argv[1] == '--start':
        cron_jobs = [task.lstrip('#') for task in cron_jobs]
    elif sys.argv[1] == '--stop':
        cron_jobs = [f'#{task}' if not task.startswith('#') else task for task in cron_jobs]
    update_crontab(cron_jobs)
    print("Crontab changed.")

if __name__ == "__main__":
    main()
