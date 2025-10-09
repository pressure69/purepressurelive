# PowerShell Network Driver Backup Guide

This guide explains how to correctly rename a network adapter and export its driver package in Windows PowerShell. It also highlights common mistakes seen when copying command output back into the console.

## Prerequisites

* An elevated (Run as Administrator) PowerShell session.
* A destination folder where the driver package will be exported.

## Rename the Adapter (Optional)

```powershell
Rename-NetAdapter -Name "Wi-Fi" -NewName "TP-Link RT3572"
```

If you only have one matching adapter name, the command completes silently. Verify the result with:

```powershell
Get-NetAdapter | Format-Table -AutoSize Name, InterfaceDescription, Status, LinkSpeed
```

## Create the Destination Folder

```powershell
New-Item -ItemType Directory -Path "C:\BackupDriver\Ralink3572" -Force | Out-Null
```

Using `Out-Null` suppresses the directory listing so that later commands do not re-run when pasted accidentally.

## Export the Driver Package

```powershell
pnputil /export-driver * "C:\BackupDriver\Ralink3572"
```

* Use `*` to export every third-party driver, or substitute the **published name** of a specific driver (for example, `oem15.inf`).
* Do **not** pass the friendly adapter name (e.g., `"Wi-Fi"`); `pnputil` requires the driver package name.

To find the published name associated with your adapter:

```powershell
pnputil /enum-drivers | Select-String -Pattern "802.11n"
```

> ⚠️ When copying command output, avoid including the prompt (for example, `PS C:\Users\...>`). Pasting a full prompt back into PowerShell causes errors such as `Get-Process : A positional parameter cannot be found...` because PowerShell interprets the prompt text as a command.

## Verifying the Export

After the export completes, confirm that `.inf`, `.cat`, and related driver files exist inside the destination directory.

```powershell
Get-ChildItem "C:\BackupDriver\Ralink3572"
```

If `pnputil` reports `Missing or invalid target directory`, double-check that the path exists and that you have write permissions. Re-run `New-Item` with `-Force` if necessary.
