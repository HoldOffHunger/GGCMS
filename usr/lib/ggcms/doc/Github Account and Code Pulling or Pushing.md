# Github Account and Code Pulling or Pushing
## _Setting Up Your Machine to Contribute and/or Work With the Project on GitHub_

### **_Install GitBash_**

Download GitBash from `git-scm.com`:

- https://git-scm.com/downloads

### **_Configure Basics_**

Sign up for a [GitHub](https://www.github.com/) account if you don't have one yet.  Set the globals for git config:

```ssh
git config --global user.name "YOURNAME"
git config --global user.email "YOUREMAIL"
```

Generate an SSH Key:

```ssh
ssh-keygen -t rsa -C "YOUREMAIL"
```

### **_Configure Advanced Settings_**

#### Paging

Most Linux commands do not use a paging system, so if you would like to disable Git's native paging system permanently, try this:

```ssh
git config --global pager.diff false
```

### **_Enable SSH Whenever Logging On_**

Every time you connect, you'll want to enable the SSH key that you generated above.  Do that with `ssh-agent`:

```sh
eval `ssh-agent`
ssh-add
```

The output will look something like:

```sh
YOURNAME@YOURNAME MINGW64 /e/Code (main)
$ eval `ssh-agent`
Agent pid 2065

YOURNAME@YOURNAME MINGW64 /e/Code (main)
$ ssh-add
Enter passphrase for /c/Users/YOURNAME/.ssh/id_rsa: YOURPASSWORD
Identity added: /c/Users/YOURNAME/.ssh/id_rsa (YOUREMAIL)
```

### **_Test Configuration Basics_**

Test with this command in GitBash:

```ssh
ssh -T git@github.com
```

You'll be asked to confirm that you accept the authenticity of github.com.  The output will look something like this:

```ssh
The authenticity of host 'github.com (140._._._)' can't be established.
ED_____ key fingerprint is SHA256:+DiY3wvv___________________________________.
This key is not known by any other names
Are you sure you want to continue connecting (yes/no/[fingerprint])? yes
Warning: Permanently added 'github.com' (ED_____) to the list of known hosts.
Enter passphrase for key '/c/Users/YOURNAME/.ssh/id_rsa':
Hi YOURNAME! You've successfully authenticated, but GitHub does not provide shell access.
```

### **_Sync Local Directory with GitHub Project Directory_**

Run this command to link a local directory to a GitHub project:

```ssh
git remote add origin git@github.com:YOURPROJECTFOUNDER/PROJECTNAME.git
```

For instance, for GGCMS2, you would do:

```ssh
git remote add origin git@github.com:YOURNAME/GreenGluonCMS2.git
```

### **_Redo the Previous Step_**

Did you mess up on the previous step?  The GitHub project name/location are case-sensitive, so, that may need changing.  In that case, try this command:

```ssh
git remote rm origin
```

Then retry again setting the origin with the previous section

### **_Pull Code from Remote Server to Local Server_**

Pull from the code base with:

```ssh
git pull
```

### **_Push Code from Local Server to Remote Server_**

Push with three simple commands:

```ssh
git add -A
git commit
git push
```

### **_See Un-Committed Changes_**

See all uncommitted changes with:

```ssh
git diff --cached
```

### **_See Committed, Unpushed Changes_**

See all unpushed changes with:

```ssh
git log origin/main..HEAD
```