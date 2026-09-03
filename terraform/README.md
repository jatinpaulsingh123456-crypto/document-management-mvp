# Terraform Deployment

This directory provisions a small AWS environment for the Document Management MVP.

## What it creates

- One VPC
- One public subnet
- Internet gateway and public route table
- Security group
- One Ubuntu EC2 VM
- One encrypted EBS volume dedicated to application file storage
- Cloud-init configuration that installs Docker, clones the repository, mounts storage, and starts Docker Compose

This is intentionally a **single-VM MVP deployment**. It is not a production multi-AZ architecture.

## Prerequisites

Install Terraform and configure AWS credentials before running the deployment.

You also need:

1. An existing EC2 key pair in the selected AWS region.
2. A Git repository URL that contains the application.
3. Your public IP address so SSH can be restricted to `<your-ip>/32`.

Verify AWS access:

```bash
aws sts get-caller-identity
```

## Configure variables

From this directory:

```bash
cp terraform.tfvars.example terraform.tfvars
```

Edit `terraform.tfvars`.

At minimum change:

```hcl
ssh_key_name     = "your-existing-keypair"
admin_cidr_blocks = ["YOUR.PUBLIC.IP/32"]
repo_url         = "https://github.com/your-account/your-repository.git"
```

Do not commit `terraform.tfvars` if it contains environment-specific or sensitive values.

## Initialize Terraform

```bash
terraform init
```

## Validate

```bash
terraform fmt -check
terraform validate
```

## Preview

```bash
terraform plan -out=tfplan
```

Review the plan before applying it.

## Apply

```bash
terraform apply tfplan
```

After the apply completes:

```bash
terraform output
```

The `server_public_ip` and `ssh_command` outputs provide the information needed to connect to the VM.

## Connect to the VM

Use the SSH command shown by Terraform, replacing `KEY_FILE` with your local private key path:

```bash
ssh -i KEY_FILE ubuntu@SERVER_IP
```

The cloud-init script installs Docker and starts the application automatically after the repository has been cloned.

You can inspect cloud-init progress with:

```bash
sudo tail -f /var/log/cloud-init-output.log
```

## Destroy

To remove the MVP infrastructure:

```bash
terraform destroy
```

This destroys the VM, network and dedicated storage volume managed by this configuration.

## State

Terraform state is created locally by default. For a shared team environment, use a remote state backend with locking and controlled access.

## MVP limitations

- AWS is used as the example provisioning target.
- A single VM is used for simplicity.
- No load balancer or multi-AZ deployment is configured.
- TLS/certificates are not provisioned by Terraform.
- Backups are not provisioned automatically.
- Monitoring and alerting are not provisioned.
- Production secrets should be provided through a secure secret-management mechanism rather than committed files.

Before production use, review SSH access, TLS, secrets management, backup policy, monitoring, patching, and the Docker runtime configuration.
