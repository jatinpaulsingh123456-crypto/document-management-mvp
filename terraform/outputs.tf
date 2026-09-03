output "server_public_ip" {
  description = "Public IPv4 address of the MVP server."
  value       = aws_instance.app.public_ip
}

output "server_public_dns" {
  description = "Public DNS name of the MVP server."
  value       = aws_instance.app.public_dns
}

output "storage_volume_id" {
  description = "ID of the encrypted EBS storage volume."
  value       = aws_ebs_volume.storage.id
}

output "vpc_id" {
  description = "VPC ID."
  value       = aws_vpc.mvp.id
}

output "security_group_id" {
  description = "Application security group ID."
  value       = aws_security_group.app.id
}

output "ssh_command" {
  description = "Example SSH command. Replace KEY_FILE with your private key path."
  value       = "ssh -i KEY_FILE ubuntu@${aws_instance.app.public_ip}"
}
