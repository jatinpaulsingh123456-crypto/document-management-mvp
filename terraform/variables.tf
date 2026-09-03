variable "project_name" {
  description = "Name used for AWS resources."
  type        = string
  default     = "document-management-mvp"
}

variable "aws_region" {
  description = "AWS region in which to provision the MVP."
  type        = string
  default     = "eu-west-2"
}

variable "vpc_cidr" {
  description = "CIDR block for the MVP VPC."
  type        = string
  default     = "10.20.0.0/16"
}

variable "public_subnet_cidr" {
  description = "CIDR block for the public subnet."
  type        = string
  default     = "10.20.1.0/24"
}

variable "instance_type" {
  description = "EC2 instance size for the MVP."
  type        = string
  default     = "t3.small"
}

variable "root_volume_size_gb" {
  description = "Root EBS volume size in GB."
  type        = number
  default     = 30
}

variable "storage_volume_size_gb" {
  description = "Separate encrypted EBS volume for application file storage."
  type        = number
  default     = 50
}

variable "ssh_key_name" {
  description = "Existing AWS EC2 key pair name used for SSH."
  type        = string
}

variable "admin_cidr_blocks" {
  description = "CIDR ranges allowed to SSH to the server. Use your public IP/32."
  type        = list(string)
}

variable "repo_url" {
  description = "Git repository URL containing the application."
  type        = string
}

variable "repo_dir" {
  description = "Directory on the VM where the repository will be cloned."
  type        = string
  default     = "/opt/document-management-mvp"
}
